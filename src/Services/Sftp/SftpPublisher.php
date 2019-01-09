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
        // Push generated file to Sharecare SFTP.
        $adapter = new SftpAdapter(
            $this->getMappedSftpConfig()
        );
        //$path = $this->sftpConfig->getFilePath() . '/' . $this->reportName;
        //$fileName = getenv('MP_ADMIN_HOST') . '/resources/app/reports/' . $this->reportName;
        $path = $this->sftpConfig->getFilePath() . '/2019-01-08-221347_sharecare_sharecare_2_Participant Point Balance.xlsx';
        $fileName = '../../../public/resources/app/reports/2019-01-08-221347_sharecare_sharecare_2_Participant Point Balance.xlsx';
        $filesystem = new Filesystem($adapter);
        //Create or overwrite if exists

        try {
            $published = $filesystem->putStream(
                $path,
                fopen($fileName, 'r+')
            );

            var_dump($published);die;
        }catch (\Exception $exception) {
            //log exception
            var_dump($exception->getMessage());die;

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
            'privateKey' => $this->sftpConfig->getKey(), //__DIR__ . '/DEV_SFTP_PRIVATE_KEY.PEM',
            'root' => $this->sftpConfig->getFilePath(), //'/home/devsftp/Reports',
            'timeout' => 10,
            'directoryPerm' => 0755
        ];
    }
}