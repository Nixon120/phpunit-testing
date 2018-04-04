<?php

namespace Controllers\User;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\User\ImportFileParser;
use Services\User\ServiceFactory;
use Services\User\UserModify;
use Slim\Views\PhpRenderer;

class UserImport extends AbstractViewController
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
        PhpRenderer $renderer,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response, $renderer);
        $this->factory = $factory;
    }

    public function renderUploadForm()
    {
        return $this->render(
            $this->getRenderer()->fetch('user/import/upload-file-form.phtml', [
                'formAction' => '/user/import/audit',
            ])
        );
    }

    public function renderAuditUploadForm()
    {

        $files = $this->request->getUploadedFiles();

        // @todo Verify this file exists before assuming it exists?
        $userImportFile = $files['userImportFile'];
        // @todo Verify file type is (application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)

        $emails = $this->getEmailsFromImportFile($userImportFile->file);

        return $this->render(
            $this->getRenderer()->fetch(
                'user/import/upload-audit-form.phtml',
                [
                    'formAction' => '/user/import/import',
                    'emails' => $emails,
                ]
            )
        );
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

        return $this->render(
            $this->getRenderer()->fetch(
                'user/import/complete.phtml',
                [
                    'errors' => $errors,
                    'users_invited' => $users_invited
                ]
            )
        );
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
