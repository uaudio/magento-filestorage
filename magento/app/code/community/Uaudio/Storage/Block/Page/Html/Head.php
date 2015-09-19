<?php

/**
 * Html page block
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Block_Page_Html_Head extends Mage_Page_Block_Html_Head {

    /**
     * Use file storage fileExists
     *
     * @param string $filename
     * @return bool
     */
    protected function _isFile($filename) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::_isFile($filename);
        }
        return Mage::getSingleton('core/file_storage')->getStorageModel()->fileExists($filename);
    }
}
