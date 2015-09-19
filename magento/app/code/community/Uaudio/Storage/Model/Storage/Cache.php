<?php

/**
 * File storage cache observer
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Storage_Cache {

    /**
     * Clear filesystem meta data cache when flushing magento system cache
     *
     * @param Varien_Event_Observer
     */
    public function flushSystem($observer) {
        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
        if(method_exists($storageModel, 'clearCache')) {
            $storageModel->clearCache();
        }
    }

    /**
     * Clear filesystem meta data cache when file_storage cache is refreshed
     *
     * @param Varien_Event_Observer
     */
    public function refreshCacheType($observer) {
        if($observer->getType() == 'file_storage') {
            $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
            if(method_exists($storageModel, 'clearCache')) {
                $storageModel->clearCache();
            }
        }
    }
}
