<?php

/**
 * Generate options for media storage selection
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_System_Config_Source_Storage_Media_Storage extends Mage_Adminhtml_Model_System_Config_Source_Storage_Media_Storage {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        return array_merge(parent::toOptionArray(), Mage::helper('uaudio_storage')->getStorageOptionArray());
    }
}
