<?php

namespace AllDigitalRewards\RewardStack\Services\Program;

use Google\Cloud\Storage\StorageClient;
use League\Flysystem\Filesystem;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

class ProgramImageFileTypeFixer
{
    /**
     * @var GoogleStorageAdapter
     */
    private $storageAdapter;

    /**
     * @param string $fileName
     * @return array
     * @throws \Exception
     */
    public function getFileIfImageFileTypeArrayExists(string $fileName): array
    {
        $bucketName = getenv('GOOGLE_CDN_BUCKET');
        $cdnPath = "https://storage.googleapis.com/$bucketName/layout";
        $fileName = $cdnPath . '/' . $fileName;
        $exif = @exif_read_data($fileName, 0, true);
        $mimeType = 'image/jpeg';
        if (is_array($exif) === true && isset($exif['FILE']) === true) {
            $mimeType = $exif['FILE']['MimeType'];
        }

        $explode = explode('/', $mimeType);
        $type = $explode[1];
        $tmpFile = '/tmp/img_file.' . $type;

        if (file_put_contents($tmpFile, file_get_contents($fileName))) {
            $contents = file_get_contents($tmpFile);
            unlink($tmpFile);
        } else {
            throw new \Exception('CDN File Download failure for ' . $fileName);
        }

        return [$contents, $type];
    }

    /**
     * @param string $fileName
     * @return mixed
     */
    public function getImageType(string $fileName)
    {
        $imageFile = explode('.', $fileName);
        return $imageFile[1];
    }

    /**
     * @param $cardName
     * @param $fileName
     * @return string
     * @throws \Exception
     */
    public function resaveCorruptedImageFile($cardName, $fileName): string
    {
        list($imageData, $type) = $this->getFileIfImageFileTypeArrayExists($fileName);
        $imagePath = md5($cardName . time()) . "." . $type;
        $this->getCdnFilesystem('layout')
            ->put($imagePath, $imageData);

        return $imagePath;
    }

    /**
     * @param $folder
     * @return Filesystem
     */
    private function getCdnFilesystem($folder)
    {
        if ($this->storageAdapter === null) {
            $storageClient = new StorageClient([
                'projectId' => getenv('GOOGLE_PROJECT_ID'),
                'keyFile' => json_decode(getenv('GOOGLE_CDN_KEY'), true),
            ]);

            $bucketName = getenv('GOOGLE_CDN_BUCKET');
            $bucket = $storageClient->bucket($bucketName);

            $this->storageAdapter = new GoogleStorageAdapter($storageClient, $bucket);
            $this->storageAdapter ->setPathPrefix($folder . '/');
        }

        return new Filesystem($this->storageAdapter);
    }
}
