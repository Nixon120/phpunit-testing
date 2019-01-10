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
     * @throws \Exception
     */
    public function publish(): bool
    {
        $adapter = new SftpAdapter(
            $this->getMappedSftpConfig()
        );
        $filesystem = new Filesystem($adapter);
        $path = '/' . $this->sftpConfig->getFilePath() . '/Participant_Enrollment.pdf';
        $fileName = __DIR__ . '/../../../public/resources/app/reports/Participant_Enrollment.pdf';

        set_error_handler(
            create_function(
                '$severity, $message, $file, $line',
                'throw new ErrorException($message, $severity, $severity, $file, $line);'
            )
        );

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

            restore_error_handler();

            throw new \Exception('Something went wrong. Could not connect to SFTP.');
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