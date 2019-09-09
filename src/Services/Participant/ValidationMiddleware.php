<?php

namespace Services\Participant;

use Particle\Validator\Exception\InvalidValueException;
use Particle\Validator\Rule\Alpha;
use Particle\Validator\Rule\Datetime;
use Particle\Validator\Rule\Email;
use Particle\Validator\Rule\Equal;
use Particle\Validator\Rule\LengthBetween;
use Particle\Validator\Rule\NotEmpty;
use Particle\Validator\Rule\Regex;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Particle\Validator\Rule\Length;
use Services\Authentication\Authenticate;
use Slim\Http\Request;
use Slim\Http\Response;
use Validation\InputValidator;
use Validation\Rules\UsPostalCode;

class ValidationMiddleware
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Authenticate
     */
    private $auth;

    /**
     * @var InputValidator
     */
    private $validator;

    private $validationMessages = [];

    private $routeArguments = [];

    private $input = [];

    private $errors = [];

    public function __construct(
        ContainerInterface $container
    )
    {

        $this->container = $container;
        $this->auth = $this->container->get('authentication');
        $this->validator = $this->container->get('validation');
    }

    /**
     * Kicks off token signing and authorization confirmation
     *
     * @param ServerRequestInterface $request
     * @param Response $response
     * @param callable|null $next
     * @return mixed
     */
    public function __invoke(
        ServerRequestInterface $request,
        Response $response,
        callable $next = null
    )
    {
        $this->request = $request;
        $this->response = $response;
        $route = $request->getAttribute('route');
        $this->routeArguments = $route->getArguments();
        $access = $this->request->getMethod() === 'GET' ? 'read' : 'write';


        $this->input = $this->request->getParsedBody() ?? [];
        $this->setValidationMessages();
        if ($access === 'write' && $this->validate() === false) {
            //if GUI we can throw one way, if API we can throw structured way, or just make JS consume it as it's rendered
            //yeah.. makes sense.
            return $this->response = $this->response->withStatus(400)
                ->withHeader('Content-type', 'application/json')
                ->withJson($this->errors);
        }

        return $next($this->request, $this->response);
    }

    private function validate()
    {
        if ($this->auth->getUser() !== null) {
            $context = $this->auth->getUser()->getRole();
        } else {
            $context = 'api';
        }

        $update = $this->request->getMethod() === 'PUT' ? true : false;

        if ($update === true) {
            return $this->validateUpdateInput($context);
        }

        return $this->validateCreateInput($context);
    }

    private function validateCreateInput($context): bool
    {
        $this->prepareContext(false);
        $result = $this->validator->validate($this->input, $context);
        if ($result->isValid()) {
            return true;
        }

        $errors = $result->getMessages();
        $this->errors = $this->setErrorMessages($errors);
        return false;
    }

    private function validateUpdateInput($context): bool
    {
        $this->prepareContext(true);
        $result = $this->validator->validate($this->input, $context);
        if ($result->isValid()) {
            return true;
        }

        $errors = $result->getMessages();
        $this->errors = $this->setErrorMessages($errors);
        return false;
    }

    private function setValidationMessages()
    {
        $this->validationMessages = [
            'organization' => [
                NotEmpty::EMPTY_VALUE => _('Organization must not be empty'),
                LengthBetween::TOO_LONG => _('Organization ID must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Organization ID must be {{ min }} characters or longer')
            ],
            'program' => [
                NotEmpty::EMPTY_VALUE => _('Program must not be empty'),
                LengthBetween::TOO_LONG => _('Program ID must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Program ID must be {{ min }} characters or longer')
            ],
            'unique_id' => [
                NotEmpty::EMPTY_VALUE => _('Unique ID must not be empty'),
                LengthBetween::TOO_LONG => _('Unique ID must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Unique ID must be {{ min }} characters or longer')
            ],
            'firstname' => [
                NotEmpty::EMPTY_VALUE => _('First Name must not be empty'),
                LengthBetween::TOO_LONG => _('First Name must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('First Name must be {{ min }} characters or longer')
            ],
            'lastname' => [
                NotEmpty::EMPTY_VALUE => _('Last Name must not be empty'),
                LengthBetween::TOO_LONG => _('Last Name must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Last Name must be {{ min }} characters or longer')
            ],
            'email_address' => [
                NotEmpty::EMPTY_VALUE => _('Email must not be empty'),
                Email::INVALID_FORMAT => _('Email must be a valid email')
            ],
            'birthdate' => [
                Datetime::INVALID_VALUE => _('Birthdate must be a valid date (YYYY-MM-DD)')
            ],
            'password_confirm' => [
                Equal::NOT_EQUAL => _('Password confirm must match password')
            ],
            'address.country_code' => [
                Alpha::NOT_ALPHA => _('Country Code must only consist out of alphabetic characters'),
                Length::TOO_LONG => _('Country Code is too long and must be 2 characters long'),
                Length::TOO_SHORT => _('Country Code is too short and must be 2 characters long')
            ],
        ];

        $this->validator->overwriteMessages($this->validationMessages);
    }

    private function prepareContext(bool $isUpdate = false)
    {
        $this->setApiUpdateContext($isUpdate);
        $this->setSuperAdminUpdateContext($isUpdate);
        $this->setClientAdminUpdateContext($isUpdate);
        $this->setConfigsAdminUpdateContext($isUpdate);
    }

    private function setApiUpdateContext(bool $isUpdate = false)
    {
        $this->validator->context('api', function (InputValidator $context) use ($isUpdate) {

            $context->optional('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
            if ($isUpdate === false) {
                $context->required('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
            }

            $context->optional('organization')->allowEmpty(false)->lengthBetween(2, 50)->string();
            $context->optional('program')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('email_address')->allowEmpty(false)->email();
            $context->optional('birthdate')->datetime('Y-m-d');
            $context->optional('password_confirm')->callback(function ($value, $values) {
                if ($value !== $values['password']) {
                    throw new InvalidValueException('Your passwords must be equal.', 'Match::DOES_NOT_MATCH');
                }
                return true;
            });
        });
    }

    private function setSuperAdminUpdateContext(bool $isUpdate = false)
    {
        $this->validator->context('superadmin', function (InputValidator $context) use ($isUpdate) {
            $context->optional('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('program')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('email_address')->allowEmpty(false)->email();
            if ($isUpdate === false) {
                $context->required('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
                $context->required('program')->allowEmpty(false)->lengthBetween(2, 45);
                $context->required('email_address')->allowEmpty(false)->email();
            }

            $context->optional('organization')->allowEmpty(false)->lengthBetween(2, 50)->string();
            $context->optional('firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('birthdate')->datetime('Y-m-d');
            if (isset($this->input['password']) === true) {
                $context->optional('password_confirm')->equals($this->input['password']);
            }
            $context->optional('address.country_code')->alpha(false)->length(2);
        });
    }

    private function setClientAdminUpdateContext(bool $isUpdate = false)
    {
        $this->validator->context('admin', function (InputValidator $context) use ($isUpdate) {
            $context->optional('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('program')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('email_address')->allowEmpty(false)->email();
            if ($isUpdate === false) {
                $context->required('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
                $context->required('program')->allowEmpty(false)->lengthBetween(2, 45);
                $context->required('email_address')->allowEmpty(false)->email();
            }

            $context->optional('organization')->allowEmpty(false)->lengthBetween(2, 50)->string();
            $context->optional('firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('birthdate')->datetime('Y-m-d');
        });
    }

    private function setConfigsAdminUpdateContext(bool $isUpdate = false)
    {
        $this->validator->context('configs', function (InputValidator $context) use ($isUpdate) {
            $context->optional('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('program')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('email_address')->allowEmpty(false)->email();
            if ($isUpdate === false) {
                $context->required('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
                $context->required('program')->allowEmpty(false)->lengthBetween(2, 45);
                $context->required('email_address')->allowEmpty(false)->email();
            }

            $context->optional('organization')->allowEmpty(false)->lengthBetween(2, 50)->string();
            $context->optional('firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('birthdate')->datetime('Y-m-d');
        });
    }

    private function setErrorMessages(array $errors)
    {
        $return = [
            'message' => _('Validation Failed'),
            'errors' => $this->getFormattedErrorMessages($errors)
        ];

        return $return;
    }

    private function getFormattedErrorMessages(array $errorMessages)
    {
        $return = [];
        foreach ($errorMessages as $key => $errors) {
            $return[$key] = $errors;
        }

        return $return;
    }
}
