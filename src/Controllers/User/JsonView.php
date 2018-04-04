<?php

namespace Controllers\User;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\User\ImportFileParser;
use Services\User\ServiceFactory;
use Services\User\UserModify;
use Slim\Views\PhpRenderer;

class JsonView extends AbstractViewController
{
    /**
     * @var ServiceFactory
     */
    private $factory;
    /**
     * @var \Services\User\UserInvite
     */
    private $userInviteService;

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

    //This method is executed after they supply their email in step 1
    public function submitRecoveryForm()
    {
        $email = $this->request->getParsedBody()['email_address'] ?? null;
        if ($email === null || !$user = $this->factory->getUserRead()->getByEmail($email)) {
            $response = $this->response->withStatus(200)
                ->withJson([
                    message => 'Sorry, invalid email and/or password.',
                    status => 'failed'

                ]);
            return $response;
            //redirect with error
        }

        $this->factory->getUserRecovery()->sendRecoveryEmail($user);

        $response = $this->response->withStatus(200)
            ->withJson([
                message => 'An email with instructions to recover your password has been sent.',
                status => 'success'
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
        $response = $this->response->withStatus(200)
            ->withJson($emails);

        return $response;
    }

    private function getEmailsFromImportFile($importFile)
    {
        $objPHPExcel = \PHPExcel_IOFactory::load($importFile);

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            //  Read a row of data into an array
            $rowData = $sheet->rangeToArray(
                'A' . $row . ':' . $highestColumn . $row,
                null,
                true,
                false
            );

            foreach ($rowData as $singleRow) {
                if ($singleRow[0] != 'Email') {
                    $emails[] = $singleRow[0];
                }
            }
        }

        return array_filter($emails, function ($e) {
            return  $e != '';
        });
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
