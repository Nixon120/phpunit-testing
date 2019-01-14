<?php
namespace Services\Sftp;

use Entities\Report;
use Entities\Sftp;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use Repositories\ReportRepository;
use Traits\LoggerAwareTrait;

class SftpPublisher
{
    use LoggerAwareTrait;
    /**
     * @var Sftp
     */
    private $sftpConfig;
    /**
     * @var ReportRepository
    /**
     * @var Report
     */
    private $report;

    /**
     * SftpPublisher constructor.
     * @param Sftp $sftpConfig
     * @param Report $report
     */
    public function __construct(
        Sftp $sftpConfig,
        Report $report
    ) {
        $this->sftpConfig = $sftpConfig;
        $this->report = $report;
    }

    /**
     * @return bool
     */
    public function publish(): bool
    {
        set_error_handler(
            create_function(
                '$severity, $message, $file, $line',
                'throw new ErrorException($message, $severity, $severity, $file, $line);'
            )
        );

        try {
            return $this->getFileSystem()->putStream(
                $this->getPath(),
                fopen($this->getFileName(), 'rb')
            );
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                'SFTP Report Publish Failure',
                [
                    'subsystem' => 'SFTP Publisher',
                    'action' => 'publish',
                    'success' => false,
                    'organization' => $this->report->getOrganization(),
                    'program' => $this->report->getProgram(),
                    'report' => $this->report->getId(),
                    'sftpConfig' => $this->getMappedSftpConfig(),
                    'error' => $exception->getMessage(),
                ]
            );

            restore_error_handler();

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
            'root' => '/home/devsftp',
            'timeout' => 10,
            'directoryPerm' => 0755
        ];
    }

    /**
     * @return Filesystem
     */
    private function getFileSystem(): Filesystem
    {
        $adapter = new SftpAdapter(
            $this->getMappedSftpConfig()
        );
        $filesystem = new Filesystem($adapter);
        return $filesystem;
    }

    /**
     * @return string
     */
    private function getPath(): string
    {
        $now = new \DateTime('now');
        $filePath =
            $this->report->getOrganization()
            . '_'
            . $this->report->getProgram()
            . '_'
            . str_replace(" ", "", $this->report->getReportName())
            . '_'
            . $now->format('Ymd')
            . '.'
            . $this->report->getFormatExtension();

        $path = '/' . $this->sftpConfig->getFilePath() . '/' . $filePath;
        return $path;
    }

    /**
     * @return string
     */
    private function getFileName(): string
    {
        if (getenv('FILESYSTEM') === 'local') {
            return __DIR__ . '/../../../public/resources/app/reports/' . $this->report->getAttachment();
        }

        return 'https://storage.googleapis.com/adrcdn/reports/' . rawurlencode($this->report->getAttachment());
    }
}