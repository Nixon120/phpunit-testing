<?php
namespace Services\Organization;

use AllDigitalRewards\IndustryProgramEnum\IndustryProgramEnum;
use AllDigitalRewards\StatusEnum\StatusEnum;
use Particle\Validator\Exception\InvalidValueException;
use Particle\Validator\Rule\Email;
use Particle\Validator\Rule\LengthBetween;
use Particle\Validator\Rule\NotEmpty;
use Particle\Validator\Rule\Regex;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use Services\Authentication\Authenticate;
use Slim\Http\Request;
use Slim\Http\Response;
use Validation\InputValidator;
use Validation\Rules\AlphanumericId;
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

    private $input = [];

    private $errors = [];

    public function __construct(
        ContainerInterface $container
    ) {
    
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
    ) {
        $this->request = $request;
        $this->response = $response;
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

    private function validate(): bool
    {
        if ($this->auth->getUser() !== null) {
            $context = $this->auth->getUser()->getRole();
            $update = isset($this->input['id']);
        } else {
            $context = 'api';
            $update = $this->request->getMethod() === 'PUT';
        }

        if ($update === true) {
            return $this->validateUpdateInput($context);
        }

        return $this->validateCreateInput($context);
    }

    private function validateCreateInput($context): bool
    {
        $this->prepareContext();
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
        $this->prepareContext();
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
            'name' => [
                NotEmpty::EMPTY_VALUE => _('Name must not be empty'),
                LengthBetween::TOO_LONG => _('Name must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Name must be {{ min }} characters or longer')
            ],
            'unique_id' => [
                NotEmpty::EMPTY_VALUE => _('Organization ID must not be empty'),
                LengthBetween::TOO_LONG => _('Organization ID must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Organization ID must be {{ min }} characters or longer'),
                AlphanumericId::INVALID_FORMAT => _('Organization ID must contain only -, _ or alphanumeric values')
            ],
            'company_contact.firstname' => [
                NotEmpty::EMPTY_VALUE => _('Company contact firstname must not be empty'),
                LengthBetween::TOO_LONG => _('Company contact firstname must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Company contact firstname must be {{ min }} characters or longer')
            ],
            'company_contact.lastname' => [
                NotEmpty::EMPTY_VALUE => _('Company contact lastname must not be empty'),
                LengthBetween::TOO_LONG => _('Company contact lastname must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Company contact lastname must be {{ min }} characters or longer')
            ],
            'company_contact.phone' => [
                NotEmpty::EMPTY_VALUE => _('Company contact phone must not be empty'),
                LengthBetween::TOO_LONG => _('Company contact phone must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Company contact phone must be {{ min }} characters or longer')
            ],
            'company_contact.email' => [
                NotEmpty::EMPTY_VALUE => _('Company contact email must not be empty'),
                Email::INVALID_FORMAT => _('Company contact email must be a valid email')
            ],
            'company_contact.address1' => [
                NotEmpty::EMPTY_VALUE => _('Company contact address1 must not be empty'),
                LengthBetween::TOO_LONG => _('Company contact address1 must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Company contact address1 must be {{ min }} characters or longer')
            ],
            'company_contact.address2' => [
                NotEmpty::EMPTY_VALUE => _('Company contact address2 must not be empty'),
                LengthBetween::TOO_LONG => _('Company contact address2 must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Company contact address2 must be {{ min }} characters or longer')
            ],
            'company_contact.city' => [
                NotEmpty::EMPTY_VALUE => _('Company contact city must not be empty'),
                LengthBetween::TOO_LONG => _('Company contact city must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Company contact city must be {{ min }} characters or longer')
            ],
            'company_contact.state' => [
                NotEmpty::EMPTY_VALUE => _('Company contact state must not be empty'),
                LengthBetween::TOO_LONG => _('Company contact state must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Company contact state must be {{ min }} characters or longer')
            ],
            'company_contact.zip' => [
                NotEmpty::EMPTY_VALUE => _('Company contact zip code must not be empty'),
                UsPostalCode::INVALID_FORMAT => _('Company contact zip code must be a valid US zip code')
            ],
            'accounts_payable_contact.firstname' => [
                NotEmpty::EMPTY_VALUE => _('Accounts payable firstname must not be empty'),
                LengthBetween::TOO_LONG => _('Accounts payable firstname must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Accounts payable firstname must be {{ min }} characters or longer')
            ],
            'accounts_payable_contact.lastname' => [
                NotEmpty::EMPTY_VALUE => _('Accounts payable lastname must not be empty'),
                LengthBetween::TOO_LONG => _('Accounts payable lastname must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Accounts payable lastname must be {{ min }} characters or longer')
            ],
            'accounts_payable_contact.phone' => [
                NotEmpty::EMPTY_VALUE => _('Accounts payable phone must not be empty'),
                LengthBetween::TOO_LONG => _('Accounts payable phone must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Accounts payable phone must be {{ min }} characters or longer')
            ],
            'accounts_payable_contact.email' => [
                NotEmpty::EMPTY_VALUE => _('Accounts payable email must not be empty'),
                Email::INVALID_FORMAT => _('Accounts payable email must be a valid email')
            ],
            'accounts_payable_contact.address1' => [
                NotEmpty::EMPTY_VALUE => _('Accounts payable address1 must not be empty'),
                LengthBetween::TOO_LONG => _('Accounts payable address1 must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Accounts payable address1 must be {{ min }} characters or longer')
            ],
            'accounts_payable_contact.address2' => [
                NotEmpty::EMPTY_VALUE => _('Accounts payable address2 must not be empty'),
                LengthBetween::TOO_LONG => _('Accounts payable address2 must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Accounts payable address2 must be {{ min }} characters or longer')
            ],
            'accounts_payable_contact.city' => [
                NotEmpty::EMPTY_VALUE => _('Accounts payable city must not be empty'),
                LengthBetween::TOO_LONG => _('Accounts payable city must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Accounts payable city must be {{ min }} characters or longer')
            ],
            'accounts_payable_contact.state' => [
                NotEmpty::EMPTY_VALUE => _('Accounts payable state must not be empty'),
                LengthBetween::TOO_LONG => _('Accounts payable state must be {{ max }} characters or shorter'),
                LengthBetween::TOO_SHORT => _('Accounts payable state must be {{ min }} characters or longer')
            ],
            'accounts_payable_contact.zip' => [
                NotEmpty::EMPTY_VALUE => _('Accounts payable zip code must not be empty'),
                UsPostalCode::INVALID_FORMAT => _('Accounts payable zip code must be a valid US zip code')
            ],

        ];

        if (!empty($this->input['domains'])) {
            foreach ($this->input['domains'] as $index => $domain) {
                $this->validationMessages['domains.' . $index . '.index'] = [
                    Regex::NO_MATCH => _('Domain URL must be valid'),
                    NotEmpty::EMPTY_VALUE => _("Domain URL must not be empty")
                ];
            }
        }
        $this->validator->overwriteMessages($this->validationMessages);
    }

    private function prepareContext()
    {
        $this->setApiUpdateContext();
        $this->setSuperAdminUpdateContext();
        $this->setClientAdminUpdateContext();
        $this->setConfigsAdminUpdateContext();
    }

    /**
     * @param $context
     * @throws InvalidValueException|ReflectionException
     */
    private function isIndustryProgramValidValueIfSet($context)
    {
        $context->optional('industry_program')->callback(function ($value, $values) {
            if ((new IndustryProgramEnum())->isValid($value) === false) {
                throw new InvalidValueException(
                    "The industry program is not valid, please refer to docs for acceptable types.",
                    'Match::DOES_NOT_MATCH'
                );
            }
            return true;
        });
    }

    private function setApiUpdateContext()
    {
        $this->validator->context('api', function (InputValidator $context) {
            $context->optional('name')->allowEmpty(false)->lengthBetween(2, 50)->string();
            $context->optional('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('company_contact.firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('company_contact.lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('company_contact.phone')->allowEmpty(false)->lengthBetween(10, 255);
            $context->optional('company_contact.email')->allowEmpty(false)->email();
            $context->optional('company_contact.address1')->allowEmpty(false)->lengthBetween(1, 255);
            $context->optional('company_contact.address2')->lengthBetween(1, 50);
            $context->optional('company_contact.city')->allowEmpty(false)->lengthBetween(1, 155);
            $context->optional('company_contact.state')->allowEmpty(false)->lengthBetween(1, 3);
            $context->optional('company_contact.zip')->allowEmpty(false)->usPostalCode();
            $context->optional('accounts_payable_contact.firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.phone')->allowEmpty(false)->lengthBetween(10, 255);
            $context->optional('accounts_payable_contact.email')->allowEmpty(false)->email();
            $context->optional('accounts_payable_contact.address1')->allowEmpty(false)->lengthBetween(1, 255);
            $context->optional('accounts_payable_contact.address2')->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.city')->allowEmpty(false)->lengthBetween(1, 155);
            $context->optional('accounts_payable_contact.state')->allowEmpty(false)->lengthBetween(1, 3);
            $context->optional('accounts_payable_contact.zip')->allowEmpty(false)->usPostalCode();

            $this->isIndustryProgramValidValueIfSet($context);
            $context->optional('domains')->eachIndex(function (InputValidator $context) {
                $context->required('index')
                    ->regex('/^[a-zA-Z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/');
            });
        });
    }

    private function setSuperAdminUpdateContext()
    {
        //@TODO can we represent this in an array, and make validation a config item?
        //Revisit.
        $this->validator->context('superadmin', function (InputValidator $context) {
            //In the GUI, all entries will be "" unless provided, so they will fail
            //In the API, the fields aren't present, so they're not validated against due to optional
            //This lets API update without the extended validation requirement
            //We should probably consider a new context, API context in the future.
            $context->optional('name')->allowEmpty(false)->lengthBetween(2, 50)->string();
            $context->optional('uniqueId')->allowEmpty(false)->lengthBetween(2, 45)->alphanumericId();
            $context->optional('company_contact.firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('company_contact.lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('company_contact.phone')->allowEmpty(false)->lengthBetween(10, 255);
            $context->optional('company_contact.email')->allowEmpty(false)->email();
            $context->optional('company_contact.address1')->allowEmpty(false)->lengthBetween(1, 255);
            $context->optional('company_contact.address2')->lengthBetween(1, 50);
            $context->optional('company_contact.city')->allowEmpty(false)->lengthBetween(1, 155);
            $context->optional('company_contact.state')->allowEmpty(false)->lengthBetween(1, 3);
            $context->optional('company_contact.zip')->allowEmpty(false)->usPostalCode();
            $context->optional('accounts_payable_contact.firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.phone')->allowEmpty(false)->lengthBetween(10, 255);
            $context->optional('accounts_payable_contact.email')->allowEmpty(false)->email();
            $context->optional('accounts_payable_contact.address1')->allowEmpty(false)->lengthBetween(1, 255);
            $context->optional('accounts_payable_contact.address2')->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.city')->allowEmpty(false)->lengthBetween(1, 155);
            $context->optional('accounts_payable_contact.state')->allowEmpty(false)->lengthBetween(1, 3);
            $context->optional('accounts_payable_contact.zip')->allowEmpty(false)->usPostalCode();

            $this->isIndustryProgramValidValueIfSet($context);
            $context->optional('domains')->eachIndex(function (InputValidator $context) {
                $context->required('index')
                    ->regex('/^[a-zA-Z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/');
            });
        });
    }

    private function setClientAdminUpdateContext()
    {
        $this->validator->context('admin', function (InputValidator $context) {
            //In the GUI, all entries will be "" unless provided, so they will fail
            //In the API, the fields aren't present, so they're not validated against due to optional
            //This lets API update without the extended validation requirement
            //We should probably consider a new context, API context in the future.
            $context->optional('name')->allowEmpty(false)->lengthBetween(1, 50)->string();
            $context->optional('parent')->allowEmpty(false)->lengthBetween(2, 45)->string();
            $context->optional('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('company_contact.firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('company_contact.lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('company_contact.phone')->allowEmpty(false)->lengthBetween(10, 255);
            $context->optional('company_contact.email')->allowEmpty(false)->email();
            $context->optional('company_contact.address1')->allowEmpty(false)->lengthBetween(1, 255);
            $context->optional('company_contact.address2')->lengthBetween(1, 50);
            $context->optional('company_contact.city')->allowEmpty(false)->lengthBetween(1, 155);
            $context->optional('company_contact.state')->allowEmpty(false)->lengthBetween(1, 3);
            $context->optional('company_contact.zip')->allowEmpty(false)->usPostalCode();
            $context->optional('accounts_payable_contact.firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.phone')->allowEmpty(false)->lengthBetween(10, 255);
            $context->optional('accounts_payable_contact.email')->allowEmpty(false)->email();
            $context->optional('accounts_payable_contact.address1')->allowEmpty(false)->lengthBetween(1, 255);
            $context->optional('accounts_payable_contact.address2')->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.city')->allowEmpty(false)->lengthBetween(1, 155);
            $context->optional('accounts_payable_contact.state')->allowEmpty(false)->lengthBetween(1, 3);
            $context->optional('accounts_payable_contact.zip')->allowEmpty(false)->usPostalCode();
            $context->optional('url')->allowEmpty(false)->url();
            $this->isIndustryProgramValidValueIfSet($context);
        });
    }

    private function setConfigsAdminUpdateContext()
    {
        $this->validator->context('configs', function (InputValidator $context) {
            //In the GUI, all entries will be "" unless provided, so they will fail
            //In the API, the fields aren't present, so they're not validated against due to optional
            //This lets API update without the extended validation requirement
            //We should probably consider a new context, API context in the future.
            $context->optional('name')->allowEmpty(false)->lengthBetween(1, 50)->string();
            $context->optional('parent')->allowEmpty(false)->lengthBetween(2, 45)->string();
            $context->optional('unique_id')->allowEmpty(false)->lengthBetween(2, 45);
            $context->optional('company_contact.firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('company_contact.lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('company_contact.phone')->allowEmpty(false)->lengthBetween(10, 255);
            $context->optional('company_contact.email')->allowEmpty(false)->email();
            $context->optional('company_contact.address1')->allowEmpty(false)->lengthBetween(1, 255);
            $context->optional('company_contact.address2')->lengthBetween(1, 50);
            $context->optional('company_contact.city')->allowEmpty(false)->lengthBetween(1, 155);
            $context->optional('company_contact.state')->allowEmpty(false)->lengthBetween(1, 3);
            $context->optional('company_contact.zip')->allowEmpty(false)->usPostalCode();
            $context->optional('accounts_payable_contact.firstname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.lastname')->allowEmpty(false)->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.phone')->allowEmpty(false)->lengthBetween(10, 255);
            $context->optional('accounts_payable_contact.email')->allowEmpty(false)->email();
            $context->optional('accounts_payable_contact.address1')->allowEmpty(false)->lengthBetween(1, 255);
            $context->optional('accounts_payable_contact.address2')->lengthBetween(1, 50);
            $context->optional('accounts_payable_contact.city')->allowEmpty(false)->lengthBetween(1, 155);
            $context->optional('accounts_payable_contact.state')->allowEmpty(false)->lengthBetween(1, 3);
            $context->optional('accounts_payable_contact.zip')->allowEmpty(false)->usPostalCode();
            $context->optional('url')->allowEmpty(false)->url();
            $this->isIndustryProgramValidValueIfSet($context);
        });
    }

    private function setErrorMessages(array $errors): array
    {
        return [
            'message' => _('Validation Failed'),
            'errors' => $this->getFormattedErrorMessages($errors)
        ];
    }

    private function getFormattedErrorMessages(array $errorMessages): array
    {
        $return = [];
        foreach ($errorMessages as $key => $errors) {
            $return[$key] = $errors;
        }

        return $return;
    }
}
