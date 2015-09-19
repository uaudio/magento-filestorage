<?php
require_once('Uaudio/vendor/autoload.php');
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

/**
 * Amazon S3 storage model
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Storage_S3 extends Uaudio_Storage_Model_Storage_Abstract {

    const STORAGE_MEDIA_ID = 2;

    /**
     * @var S3 access key
     */
    protected $_key;

    /**
     * @var S3 secret key
     */
    protected $_secret;

    /**
     * @var S3 region
     */
    protected $_region;

    /**
     * @var S3 bucket
     */
    protected $_bucket;

    /**
     * Initialize S3 settings
     *
     * @param array - allow settings override during synchronization
     */
    public function __construct($settings=[]) {
        $this->_key = isset($settings['s3_access_key']) ?  $settings['s3_access_key'] : Mage::getStoreConfig('system/media_storage_configuration/media_s3_access_key');
        $this->_secret = isset($settings['s3_secret_key']) ? $settings['s3_secret_key'] : Mage::getStoreConfig('system/media_storage_configuration/media_s3_secret_key');
        $this->_region = isset($settings['s3_region'])? $settings['s3_region'] : Mage::getStoreConfig('system/media_storage_configuration/media_s3_region');
        $this->_bucket = isset($settings['s3_bucket']) ? $settings['s3_bucket'] : Mage::getStoreConfig('system/media_storage_configuration/media_s3_bucket');
        $this->_folder = isset($settings['s3_folder']) ? $settings['s3_folder'] : Mage::getStoreConfig('system/media_storage_configuration/media_s3_folder');
        parent::__construct();
    }

    /**
     * Get storage name
     *
     * @return string
     */
    public function getStorageName() {
        return Mage::helper('core')->__('Amazon S3');
    }

    /**
     * Get the settings for this storage type
     *
     * @return array
     */
    public function settings() {
        return [
            's3_access_key' => 'Access Key',
            's3_secret_key' => 'Secret Key',
            's3_region'     => 'Region',
            's3_bucket'     => 'Bucket',
            's3_folder'     => 'Folder (optional)'
        ];
    }

    /**
     * Get flysystem adapter
     *
     * @return \League\Flysystem\AwsS3v2\AwsS3Adapter
     */
    protected function _getAdapter() {
        if(!$this->_adapter) {
            $client = S3Client::factory([
                'credentials' => [
                    'key'    => $this->_key,
                    'secret' => $this->_secret,
                ],
                'region' => $this->_region,
                'version' => '2006-03-01',
            ]);
            $this->_adapter = new AwsS3Adapter($client, $this->_bucket, $this->_folder);
        }
        return $this->_adapter;
    }
}
