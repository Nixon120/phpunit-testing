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
     */
    private $reportRepository;
    /**
     * @var int
     */
    private $reportId;
    /**
     * @var Report
     */
    private $report;

    /**
     * SftpPublisher constructor.
     * @param Sftp $sftpConfig
     * @param ReportRepository $reportRepository
     * @param int $reportId
     */
    public function __construct(
        Sftp $sftpConfig,
        ReportRepository $reportRepository,
        int $reportId
    ) {
        $this->sftpConfig = $sftpConfig;
        $this->reportRepository = $reportRepository;
        $this->reportId = $reportId;
    }

    /**
     * @return bool
     */
    public function publish(): bool
    {
        $path = '/' . $this->sftpConfig->getFilePath() . '/' . $this->getReport()->getAttachment();
        $fileName = __DIR__ . '/../../../public/resources/app/reports/' . $this->getReport()->getAttachment();

        set_error_handler(
            create_function(
                '$severity, $message, $file, $line',
                'throw new ErrorException($message, $severity, $severity, $file, $line);'
            )
        );

        try {
            return $this->getFileSystem()->putStream($path, fopen($fileName, 'r+'));
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                'SFTP Report Publish Failure',
                [
                    'subsystem' => 'SFTP Publisher',
                    'action' => 'publish',
                    'success' => false,
                    'organization' => $this->getReport()->getOrganization(),
                    'program' => $this->getReport()->getProgram(),
                    'report' => $this->getReport()->getId(),
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
            'root' => SFTP_ROOT_DIR,
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
     * @return \Entities\Report|null
     */
    public function getReport(): ?Report
    {
        if (is_null($this->report) === true) {
            $this->report = $this->reportRepository
                ->getReportById($this->reportId);
        }

        return $this->report;
    }
}