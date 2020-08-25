<?php

namespace Controllers\User;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\User\ServiceFactory;
use Traits\LoggerAwareTrait;

class JsonView extends AbstractViewController
{
    use LoggerAwareTrait;
    /**
     * @var ServiceFactory
     */
    private $factory;
    /**
     * @var \Services\User\UserInvite
     */
    private $userInviteService;
    /**
     * @var string
     */
    private $errorMessage = '';

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->factory = $factory;
    }

    public function listResult()
    {
        $get = $this->request->getQueryParams();
        $input = new InputNormalizer($get);
        $users = $this->factory->getUserRead()->get($input);

        $response = $this->response->withStatus(200)
            ->withJson($users);
        return $response;
    }

    // This method handles directing traffic for the password change request
    public function submitRecoveryPassword($token)
    {
        if (!$user = $this->factory->getUserRead()->getByInviteToken($token)) {
            return $this->response->withStatus(200)
                ->withJson([
                    'message' => 'Sorry, then token is invalid or expired.',
                    'status' => 'failed'

                ]);
        }

        $password = $this->request->getParsedBody()['password'] ?? null;
        $confirm = $this->request->getParsedBody()['confirm'] ?? null;

        if ($password !== $confirm) {
            return $this->response->withStatus(200)
                ->withJson([
                    'message' => 'Password and password confirmation did not match',
                    'status' => 'failed'
                ]);
        }

        $this->factory->getUserModify()
            ->update($user->getId(), [
                'password' => $password,
                'invite_token' => ''
            ]);

        return $this->response->withStatus(200)
            ->withJson([
                'message' => 'Your password changed successfully.',
                'status' => 'success'
            ]);
    }

    //This method is executed after they supply their email in step 1
    public function submitRecoveryForm()
    {
        $email = $this->request->getParsedBody()['email_address'] ?? null;
        if ($email === null || !$user = $this->factory->getUserRead()->getByEmail($email)) {
            $response = $this->response->withStatus(200)
                ->withJson([
                    'message' => 'Sorry, invalid email and/or password.',
                    'status' => 'failed'
                ]);
            return $response;
            //redirect with error
        }

        $result = $this->factory->getUserRecovery()->sendRecoveryEmail($user);
        if ($result === true) {
            $response = $this->response->withStatus(200)
                ->withJson([
                    'message' => 'An email with instructions to recover your password has been sent.',
                    'status' => 'success'
                ]);
            return $response;
        }

        $response = $this->response->withStatus(400)
            ->withJson([
                'message' => implode('<br />', $this->factory->getUserRecovery()->getErrors()),
                'status' => 'failed'
            ]);
        return $response;
    }

    public function auditUploadFormData()
    {
        $files = $this->request->getUploadedFiles();

        // @todo Verify this file exists before assuming it exists?
        $userImportFile = $files['userImportFile'];
        // @todo Verify file type is (application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)

        $emails = $this->getEmailsFromImportFile($userImportFile->file);
        if (empty($emails) === false) {
            $response = $this->response->withStatus(200)
                ->withJson($emails);
            return $response;
        }

        $response = $this->response->withStatus(400)
            ->withJson([
                'message' => $this->errorMessage,
                'status' => 'failed'
            ]);
        return $response;
    }

    private function getEmailsFromImportFile($importFile)
    {
        $emails = [];
        try {
            $reader = ReaderFactory::createFromType(Type::XLSX);
            $reader->open($importFile);
            $cnt = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    if ($cnt > 0) {
                        $cells = $row->getCells();
                        $emails[] = $cells[0]->getValue();
                    }
                    $cnt++;
                }
            }
            $emailContainer = array_filter($emails, function ($e) {
                return  $e != '';
            });
            if (empty($emailContainer) === true) {
                $this->errorMessage = 'No emails were found in the file';
            }

            return $emailContainer;
        } catch (\Exception $exception) {
            $this->errorMessage = $exception->getMessage();
            $this->getLogger()->error(
                'GetEmailsFromImportFile File Reading Failure',
                [
                    'subsystem' => 'User Email Upload',
                    'action' => 'Read',
                    'success' => false,
                    'error' => $exception->getMessage()
                ]
            );
        }

        return $emails;
    }

    public function importUsers()
    {
        $userData = $this->request->getParsedBody();
        $errors = [];
        $users_invited = 0;

        for ($i = 0; $i < count($userData['email_address']); $i++) {
            $user = [
                'email_address' => $userData['email_address'][$i],
                'role' => $userData['role'][$i],
                'organization' => $userData['organization'][$i],
                'firstname' => '',
                'lastname' => '',
                'password' => 'Password@123'
            ];

            $result = $this->getUserInviteService()->invite($user);
            if ($result === true) {
                $users_invited++;
            } else {
                $errors[] = 'Could not invite ' . $userData['email_address'][$i] . ', There is already a user with that email address registered!';
            }
        }


        $returnStatus = [
            'errors' => $errors,
            'users_invited' => $users_invited
        ];

        $response = $this->response->withStatus(200)
            ->withJson($returnStatus);

        return $response;
    }

    /**
     * @return \Services\User\UserInvite
     */
    private function getUserInviteService()
    {
        if (is_null($this->userInviteService)) {
            $this->userInviteService = $this->factory->getUserInvite();
        }

        return $this->userInviteService;
    }
}
