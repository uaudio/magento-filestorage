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

    protected $_baseFileSet = false;

    /**
     * Reset all previous data
     *
     * @return self
     */
    protected function _reset() {
        $this->_originalWidth = null;
        $this->_originalHeight = null;
        $this->_baseFileSet = false;
        return parent::_reset();
    }

    /**
     * Initialize Helper to work with Image
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $attributeName
     * @param mixed $imageFile
     * @return Mage_Catalog_Helper_Image
     */
    public function init(Mage_Catalog_Model_Product $product, $attributeName, $imageFile=null) {
        $this->_reset();
        $this->_setModel(Mage::getModel('catalog/product_image'));
        $this->_getModel()->setDestinationSubdir($attributeName);
        $this->setProduct($product);

        $this->setWatermark(
            Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_image")
        );
        $this->setWatermarkImageOpacity(
            Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_imageOpacity")
        );
        $this->setWatermarkPosition(
            Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_position")
        );
        $this->setWatermarkSize(
            Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_size")
        );

        if ($imageFile) {
            $this->setImageFile($imageFile);
        }
        return $this;
    }

    /**
     * Retrieve original image width
     *
     * @return int|null
     */
    public function getOriginalWidth() {
        if($this->_originalWidth) return $this->_originalWidth;
        if(!$this->_baseFileSet) {
            $this->_getModel()->setBaseFile($this->getProduct()->getData($this->_getModel()->getDestinationSubdir()));
            $this->_baseFileSet = true;
        }

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
        if(!$this->_baseFileSet) {
            $this->_getModel()->setBaseFile($this->getProduct()->getData($this->_getModel()->getDestinationSubdir()));
            $this->_baseFileSet = true;
        }

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
