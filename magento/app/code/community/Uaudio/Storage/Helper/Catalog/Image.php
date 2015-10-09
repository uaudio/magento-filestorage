<?php

/**
 * Catalog image helper
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Helper_Catalog_Image extends Mage_Catalog_Helper_Image {

    /**
     * Original image width
     *
     * @var int
     */
    protected $_originalWidth = null;

    /**
     * Original image height
     *
     * @var int
     */
    protected $_originalHeight = null;

    /**
     * Reset all previous data
     *
     * @return self
     */
    protected function _reset() {
        $this->_originalWidth = null;
        $this->_originalHeight = null;
        return parent::_reset();
    }
    
    /**
     * Retrieve original image width
     *
     * @return int|null
     */
    public function getOriginalWidth() {
        if($this->_originalWidth) return $this->_originalWidth;

        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::getOriginalWidth();
        }

        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
        $metadata = $storageModel->getMetadata($this->_getModel()->getBaseFile());
        if(isset($metadata['width'])) {
            $this->_originalWidth = $metadata['width'];
        } else {
            $this->_updateMetadata();
        }
        return $this->_originalWidth;
    }

    /**
     * Retrieve original image height
     *
     * @return int|null
     */
    public function getOriginalHeight() {
        if($this->_originalHeight) return $this->_originalHeight;

        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::getOriginalHeight();
        }
        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
        $metadata = $storageModel->getMetadata($this->_getModel()->getBaseFile());
        if(isset($metadata['height'])) {
            $this->_originalHeight = $metadata['height'];
        } else {
            $this->_updateMetadata();
        }

        return $this->_originalHeight;
    }

    /**
     * Update height & width in storage metadata
     *
     * @return self
     */
    protected function _updateMetadata() {
        $this->_originalWidth = parent::getOriginalWidth();
        $this->_originalHeight = parent::getOriginalHeight();

        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
        $storageModel->updateMetadata($this->_getModel()->getBaseFile(), [
            'width' => $this->_originalWidth,
            'height' => $this->_originalHeight,
        ]);
        return $this;
    }
}
