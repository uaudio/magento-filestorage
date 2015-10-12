<?php
require_once('Uaudio/vendor/autoload.php');

/**
 * Flysystem redis cache using magento cache settings
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Storage_Cache_Redis extends League\Flysystem\Cached\Storage\AbstractCache {

    protected $key = 'file_storage';

    /**
     * Load the cache
     */
    public function load() {
        $contents = Mage::app()->loadCache($this->key);
        if($contents) {
            $this->setFromStorage(gzuncompress($contents));
        }
    }

    /**
     * Save the cache
     */
    public function save() {
        $contents = gzcompress($this->getForStorage());
        Mage::app()->saveCache($contents, $this->key, ['FILE_STORAGE'], $this->expire);
    }
}
