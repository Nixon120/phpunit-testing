<?php
namespace Services\Sftp;

use Entities\Report;
use Entities\Sftp;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use Traits\LoggerAwareTrait;

class SftpService
{
    use LoggerAwareTrait;
    /**
     * @var Sftp
     */
    private $sftpConfig;
    /**
     * @var Report
     */
    private $report;
    /**
     * @var string
     */
    private $path;
    /**
     * @var array
     */
    private $mappedSftpConfig;

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
     * @return bool|false|string
     */
    public function fetchFile()
    {
        set_error_handler(
            create_function(
                '$severity, $message, $file, $line',
                'throw new ErrorException($message, $severity, $severity, $file, $line);'
            )
        );
        try {
            return $this->getFileSystem()->read($this->getPath());
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                'SFTP Report File Fetcher Failure',
                [
                    'subsystem' => 'SFTP File Fetcher',
                    'action' => 'retrieve',
                    'success' => false,
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
        if (is_null($this->mappedSftpConfig) === true) {
            $this->mappedSftpConfig = [
                'host' => $this->getSftpConfig()->getHost(),
                'port' => $this->getSftpConfig()->getPort(),
                'username' => $this->getSftpConfig()->getUsername(),
                'password' => $this->getSftpConfig()->getPassword(),
                'privateKey' => strip_tags($this->getSftpConfig()->getKey()),
                'timeout' => 10,
                'directoryPerm' => 0755
            ];
        }

        return $this->mappedSftpConfig;
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
     * @return Sftp
     */
    public function getSftpConfig(): Sftp
    {
        return $this->sftpConfig;
    }

    /**
     * @param Sftp $sftpConfig
     */
    public function setSftpConfig(Sftp $sftpConfig)
    {
        $this->sftpConfig = $sftpConfig;
    }

    /**
     * @return Report
     */
    public function getReport(): Report
    {
        return $this->report;
    }

    /**
     * @param Report $report
     */
    public function setReport(Report $report)
    {
        $this->report = $report;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    private function getFileName(): string
    {
        if (getenv('FILESYSTEM') === 'local') {
            return __DIR__ . '/../../../public/resources/app/reports/' . $this->getReport()->getAttachment();
        }

        $bucket = getenv('GOOGLE_CDN_BUCKET');
        return 'https://storage.googleapis.com/'. $bucket .'/reports/' . rawurlencode($this->getReport()->getAttachment());
    }
}
