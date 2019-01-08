<?php
namespace Controllers\Report;

use Controllers\AbstractInputNormalizer;

class InputNormalizer extends AbstractInputNormalizer
{
    public function isFieldSelected($key)
    {
        $input = $this->getInput();
        if (in_array($key, $input['fields'])) {
            return true;
        }

        return false;
    }

    public function getUniqueId()
    {
        return $this->getInput()['unique_id'] ?? '';
    }

    public function getFirstname()
    {
        return $this->getInput()['firstname'] ?? '';
    }

    public function getLastname()
    {
        return $this->getInput()['lastname'] ?? '';
    }

    public function getAddress1()
    {
        return $this->getInput()['address1'] ?? '';
    }

    public function getAddress2()
    {
        return $this->getInput()['address2'] ?? '';
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        $input = $this->getInput();
        if (empty($input['start_date'])) {
            return '';
        }

        return $this->getDate('start_date')->format('Y-m-d');
    }

    /**
     * @return string
     */
    public function getEndDate()
    {
        $input = $this->getInput();

        if (empty($input['end_date'])) {
            return '';
        }

        return $this->getDate('end_date')->format('Y-m-d');
    }

    /**
     * @param $range
     * @return \DateTime
     */
    private function getDate($range):\DateTime
    {
        $input = $this->getInput();

        $date = new \DateTime;
        $timestamp = strtotime($input[$range]);
        $date->setTimestamp($timestamp);
        return $date;
    }

    public function getOrganzationUuid(): ?string
    {
        $input = $this->getInput();
        return $input['organization'] ?? null;
    }

    public function getProgramUuid(): ?string
    {
        $input = $this->getInput();
        return $input['program'] ?? null;
    }

    public function getReportType(): string
    {
        $input = $this->getInput();
        return $input['report'] ?? '';
    }

    public function isCriteriaBeingUpdated()
    {
        $input = $this->getInput();
        return isset($input['update']) ? true : false;
    }

    public function getRequestedFields()
    {
        $input = $this->getInput();
        return $input['fields'] ?? [];
    }

    public function getMetaFields()
    {
        $input = $this->getInput();
        return $input['meta'] ?? [];
    }

    public function getReportOutput()
    {
        $input = $this->getInput();
        $output = $input['report_output'];

        if ($output === 'download') {
            return 'file';
        }

        return 'html';
    }

    public function getUser()
    {
        $input = $this->getInput();
        $user = $input['user'] ?? null;

        return $user;
    }

    public function getSftp()
    {
        $input = $this->getInput();
        $output = $input['sftp'] ?? null;

        if (is_null($output) === false) {
            $this->setSftpIsProcessed(0);
            return $input['sftp'];
        }

        return null;
    }

    public function getSftpProcessed()
    {
        $input = $this->getInput();
        $output = $input['sftp_processed'] ?? null;

        if (is_null($output) === false) {
            return $input['sftp_processed'];
        }

        return 0;
    }

    public function setSftpIsProcessed(int $processed)
    {
        $input = $this->getInput();
        $input['sftp_processed'] = $processed;
    }
}
