<?php

namespace AllDigitalRewards\RewardStack\Services\Program;

class ProgramImageFileTypeFixer
{
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
}
