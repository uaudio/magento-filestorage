<?php
require_once('Uaudio/vendor/autoload.php');
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Util;
use League\Flysystem\Config;

/**
 * Extend adapter to add image size to S3 metadata
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_League_AwsS3Adapter extends AwsS3Adapter {

    /**
     * Upload an object.
     *
     * @param        $path
     * @param        $body
     * @param Config $config
     *
     * @return array
     */
    protected function upload($path, $body, Config $config) {
        $key = $this->applyPathPrefix($path);

        $return = parent::upload($path, $body, $config);
        if(function_exists('getimagesizefromstring')) {
            $size = getimagesizefromstring($body);
            $this->s3Client->copyObject([
                'Bucket' => $this->bucket,
                'CopySource' => $this->bucket.DS.$key,
                'ContentType' => $return['mimetype'],
                'Metadata' => [
                    'x-amz-meta-width' => $size[0],
                    'x-amz-meta-height' => $size[1],
                ],
                'MetadataDirective' => 'REPLACE',
                'Key' => $key,
            ]);
        }
        return $return;
    }

    /**
     * Normalize the object result array.
     *
     * @param array $response
     *
     * @return array
     */
    protected function normalizeResponse(array $response, $path = null) {
        $map = [
            'x-amz-meta-width' => 'width',
            'x-amz-meta-height' => 'height',
        ];
        $result = array_merge(parent::normalizeResponse($response, $path), Util::map($response['Metadata'], $map));
        return $result;
    }
}
