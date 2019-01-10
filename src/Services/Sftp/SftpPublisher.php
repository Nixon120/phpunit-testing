<?php
namespace Services\Sftp;

use Entities\Sftp;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use Traits\LoggerAwareTrait;

class SftpPublisher
{
    use LoggerAwareTrait;

    /**
     * @var Sftp
     */
    private $sftpConfig;
    private $reportName;

    public function __construct(Sftp $sftpConfig, string $reportName)
    {
        $this->sftpConfig = $sftpConfig;
        $this->reportName = $reportName;
    }

    public function publish()
    {
        // Push generated file to SFTP.
        $adapter = new SftpAdapter(
            $this->getMappedSftpConfig()
        );
        $filesystem = new Filesystem($adapter);
        $path = '/home/devsftp/Reports/' . $this->sftpConfig->getFilePath() . '/2019-01-10-134302_sharecare_sharecare_42_Participant Enrollment.pdf';
        $fileName = __DIR__ . '/../../../public/resources/app/reports/2019-01-10-134302_sharecare_sharecare_42_Participant Enrollment.pdf';

        try {
            return $filesystem->putStream($path, fopen($fileName, 'r+'));
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                'SFTP Report Publish Failure',
                [
                    'subsystem' => 'SFTP Publisher',
                    'action' => 'publish',
                    'success' => false,
                    'organization' => 'sharecare',
                    'program' => 'sharecare',
                    'report' => $this->reportName,
                    'sftpConfig' => $this->getMappedSftpConfig(),
                    'error' => $exception->getMessage(),
                ]
            );

            return false;
        }

    }

    private function getMappedSftpConfig()
    {
        return [
            'host' => $this->sftpConfig->getHost(), //'sftp.staging.alldigitalrewards.com',
            'port' => $this->sftpConfig->getPort(), //22,
            'username' => $this->sftpConfig->getUsername(), //'devsftp',
            'password' => $this->sftpConfig->getPassword(), //'',
            'privateKey' => strip_tags($this->sftpConfig->getKey()),
            'root' => '/home/devsftp/Reports',
            'timeout' => 10,
            'directoryPerm' => 0755
        ];
    }
}