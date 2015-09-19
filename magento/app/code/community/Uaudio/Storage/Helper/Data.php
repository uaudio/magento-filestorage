<?php

/**
 * File storage helper
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Helper_Data extends Mage_Core_Helper_File_Storage {
    
    /**
     * List of defined storage options
     *
     * @var array
     */
    protected $_options = null;

    /**
     * Check if one of the modules storage options is enabled
     *
     * @return bool
     */
    public function isEnabled() {
        $options = $this->getStorageOptions();

        return isset($options[$this->getCurrentStorageCode()]);
    }

    /**
     * Get uaudio storage options from config
     *
     * @return array
     */
    public function getStorageOptions() {
        if(!$this->_options) {
            $options = Mage::getConfig()->getNode(Uaudio_Storage_Model_Storage::XML_CONFIG_PATH);
            foreach($options->children() as $option) {
                $modelClass = $option->class->__toString();
                $className = Mage::getConfig()->getModelClassName($modelClass);
                $this->_options[$className::STORAGE_MEDIA_ID] = [
                    'index'     => $className::STORAGE_MEDIA_ID,
                    'label'     => Mage::helper('uaudio_storage')->__($option->name->__toString()),
                    'model'     => $modelClass,
                    'class'     => $className,
                ];
            }
        }
        return $this->_options;
    }

    /**
     * Get storage options as an option array
     *
     * @return array
     */
    public function getStorageOptionArray() {
        $return = [];
        $options = $this->getStorageOptions();
        foreach($options as $option) {
            $return[] = [
                'value'     => $option['index'],
                'label'     => $option['label']
            ];
        }
        return $return;
    }
}
