<?php
require_once('Uaudio/vendor/autoload.php');
use League\Flysystem\Adapter\Local;

/**
 * Local system storage
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Storage_Local extends Uaudio_Storage_Model_Storage_Abstract {

    const STORAGE_MEDIA_ID = 3;

    /**
     * Get storage name
     *
     * @return string
     */
    public function getStorageName() {
        return Mage::helper('core')->__('Local');
    }

    /**
     * Initialize Local adapter settings
     *
     * @param array - allow settings override during synchronization
     */
    public function __construct($settings=[]) {
        $this->_folder = isset($settings['local_folder']) ? $settings['local_folder'] : Mage::getStoreConfig('system/media_storage_configuration/media_local_folder');
        parent::__construct();
    }

    /**
     * Get the settings for this storage type
     *
     * @return array
     */
    public function settings() {
        return [
            'local_folder' => 'Folder (full path to folder)'
        ];
    }

    /**
     * Get flysystem adapter
     *
     * @return \League\Flysystem\Local
     */
    protected function _getAdapter() {
        if(!$this->_adapter) {
            $this->_adapter = new Local($this->_folder);
        }
        return $this->_adapter;
    }
}
