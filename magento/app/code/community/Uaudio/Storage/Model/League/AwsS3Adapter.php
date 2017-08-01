<?php
require_once('vendor/autoload.php');
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Util;
use League\Flysystem\Util\MimeType;
use League\Flysystem\Config;

/**
 * Extend AWS adapter to add image size to S3 metadata
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
        $mimetype = MimeType::detectByFileExtension(pathinfo($path, PATHINFO_EXTENSION));
        $config->set('mimetype', $mimetype);

        $return = parent::upload($path, $body, $config);
        $metadata = [];
        if(function_exists('getimagesizefromstring') && strpos($mimetype, 'image')!==false) {
            if(is_resource($body)) {
                rewind($body);
                $size = getimagesizefromstring(stream_get_contents($body));
            } else {
                $size = getimagesizefromstring($body);
            }
            $metadata = [
                'Metadata' => [
                    'width' => $size[0],
                    'height' => $size[1]
                ]
            ];
        }
        $copyArray = array_merge([
            'Bucket' => $this->bucket,
            'CopySource' => $this->bucket.DS.$key,
            'ContentType' => $mimetype,
            'Key' => $key,
            'MetadataDirective' => 'REPLACE'
        ], $metadata);
        if(isset($this->options['cachecontrol'])) {
            $cachecontrol = 'max-age='.$this->options['cachecontrol'].', public';
            $copyArray['CacheControl'] = $cachecontrol;
        }
        $this->s3Client->copyObject($copyArray);
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
        $result = parent::normalizeResponse($response, $path);
        if(isset($response['Metadata']) && is_array($response['Metadata'])) {
            $result = array_merge($result, $response['Metadata']);
        }
        return $result;
    }

    /**
     * Add metadata to S3 object
     *
     * @param string
     * @param array
     */
    public function updateMetadata($file, $metadata) {
        $meta = $this->getMetadata($file);
        $key = $this->applyPathPrefix($file);
        $metadata = array_merge($meta['metadata'], $metadata);
        $copyArray = [
            'Bucket' => $this->bucket,
            'CopySource' => $this->bucket.DS.$key,
            'ContentType' => $meta['mimetype'],
            'Metadata' => ['metadata' => json_encode($metadata)],
            'MetadataDirective' => 'REPLACE',
            'Key' => $key
        ];
        if (isset($this->options['cachecontrol'])) {
            $cachecontrol = 'max-age='.$this->options['cachecontrol'].', public';
            $copyArray['CacheControl'] = $cachecontrol;
        }
        $this->s3Client->copyObject($copyArray);
    }
}
