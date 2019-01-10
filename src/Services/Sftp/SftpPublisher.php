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
    private $reportName = '';
    private $organization = '';
    private $program = '';

    /**
     * SftpPublisher constructor.
     * @param Sftp $sftpConfig
     * @param string $reportName
     * @param string $organization
     * @param string $program
     */
    public function __construct(
        Sftp $sftpConfig,
        string $reportName,
        string $organization,
        string $program
    ) {
        $this->sftpConfig = $sftpConfig;
        $this->reportName = $reportName;
        $this->organization = $organization;
        $this->program = $program;
    }

    /**
     * @return bool
     */
    public function publish(): bool
    {
        $adapter = new SftpAdapter(
            $this->getMappedSftpConfig()
        );
        $filesystem = new Filesystem($adapter);
        $path = '/' . $this->sftpConfig->getFilePath() . '/2019-01-10-134302_sharecare_sharecare_42_Participant Enrollment.pdf';
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
                    'organization' => $this->organization,
                    'program' => $this->program,
                    'report' => $this->reportName,
                    'sftpConfig' => $this->getMappedSftpConfig(),
                    'error' => $exception->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * @return array
     */
    private function getMappedSftpConfig(): array
    {
        return [
            'host' => $this->sftpConfig->getHost(),
            'port' => $this->sftpConfig->getPort(),
            'username' => $this->sftpConfig->getUsername(),
            'password' => $this->sftpConfig->getPassword(),
            'privateKey' => strip_tags($this->sftpConfig->getKey()),
            'root' => SFTP_ROOT_DIR,
            'timeout' => 10,
            'directoryPerm' => 0755
        ];
    }
}