<?php

/**
 * Catalog product image model
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Catalog_Product_Image extends Mage_Catalog_Model_Product_Image {
    
    /**
     * Get file storage model
     *
     * @return Mage_Core_Model_File_Storage_Abstract
     */
    protected function _getStorageModel() {
        return Mage::getSingleton('core/file_storage')->getStorageModel();
    }

    /**
     * Set filenames for base file and new file
     *
     * @param string $file
     * @return self
     */
    public function setBaseFile($file) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::setBaseFile($file);
        }

        $this->_isBaseFilePlaceholder = false;

        if (($file) && (0 !== strpos($file, '/', 0))) {
            $file = '/' . $file;
        }
        $baseDir = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();

        if ('/no_selection' == $file) {
            $file = null;
        }

        if ($file) {
            if ((!$this->_fileExists($baseDir . $file)) || !$this->_checkMemory($baseDir . $file)) {
                $file = null;
            }
        }

        if (!$file) {
            $isConfigPlaceholder = Mage::getStoreConfig("catalog/placeholder/{$this->getDestinationSubdir()}_placeholder");
            $configPlaceholder   = '/placeholder/' . $isConfigPlaceholder;
            if ($isConfigPlaceholder && $this->_fileExists($baseDir . $configPlaceholder)) {
                $file = $configPlaceholder;
            }
            else {
                // replace file with skin or default skin placeholder
                $skinBaseDir     = Mage::getDesign()->getSkinBaseDir();
                $skinPlaceholder = "/images/catalog/product/placeholder/{$this->getDestinationSubdir()}.jpg";
                $file = $skinPlaceholder;
                if (file_exists($skinBaseDir . $file)) {
                    $baseDir = $skinBaseDir;
                }
                else {
                    $baseDir = Mage::getDesign()->getSkinBaseDir(array('_theme' => 'default'));
                    if (!file_exists($baseDir . $file)) {
                        $baseDir = Mage::getDesign()->getSkinBaseDir(array('_theme' => 'default', '_package' => 'base'));
                    }
                }
            }
            $this->_isBaseFilePlaceholder = true;
        }

        $baseFile = $baseDir . $file;
        if ((!$file) || (!$this->_fileExists($baseFile))) {
            throw new Exception(Mage::helper('catalog')->__('Image file was not found.'));
        }

        $this->_baseFile = $baseFile;

        // build new filename (most important params)
        $path = array(
            Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath(),
            'cache',
            Mage::app()->getStore()->getId(),
            $path[] = $this->getDestinationSubdir()
        );
        if((!empty($this->_width)) || (!empty($this->_height)))
            $path[] = "{$this->_width}x{$this->_height}";

        // add misk params as a hash
        $miscParams = array(
                ($this->_keepAspectRatio  ? '' : 'non') . 'proportional',
                ($this->_keepFrame        ? '' : 'no')  . 'frame',
                ($this->_keepTransparency ? '' : 'no')  . 'transparency',
                ($this->_constrainOnly ? 'do' : 'not')  . 'constrainonly',
                $this->_rgbToString($this->_backgroundColor),
                'angle' . $this->_angle,
                'quality' . $this->_quality
        );

        // if has watermark add watermark params to hash
        if ($this->getWatermarkFile()) {
            $miscParams[] = $this->getWatermarkFile();
            $miscParams[] = $this->getWatermarkImageOpacity();
            $miscParams[] = $this->getWatermarkPosition();
            $miscParams[] = $this->getWatermarkWidth();
            $miscParams[] = $this->getWatermarkHeigth();
        }

        $path[] = md5(implode('_', $miscParams));

        // append prepared filename
        $this->_newFile = implode('/', $path) . $file; // the $file contains heading slash

        return $this;
    }
    
    /**
     * First check this file on FS
     * If it doesn't exist - try to download it from DB
     *
     * @param string $filename
     * @return bool
     */
    protected function _fileExists($filename) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::_fileExists($filename);
        }

        return $this->_getStorageModel()->fileExists($filename);
    }

    protected function _getNeedMemoryForFile($file = null) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::_getNeedMemoryForFile($file);
        }

        $file = is_null($file) ? $this->getBaseFile() : $file;
        if (!$file) {
            return 0;
        }

        if (!$this->_fileExists($file)) {
            return 0;
        }

        if(strstr($file, Mage::getBaseDir('media'))) {
            $imageInfo = getimagesize($this->_getFileFromStorage($file));
        } else {
            $imageInfo = getimagesize($file);
        }

        if (!isset($imageInfo[0]) || !isset($imageInfo[1])) {
            return 0;
        }
        if (!isset($imageInfo['channels'])) {
            // if there is no info about this parameter lets set it for maximum
            $imageInfo['channels'] = 4;
        }
        if (!isset($imageInfo['bits'])) {
            // if there is no info about this parameter lets set it for maximum
            $imageInfo['bits'] = 8;
        }

        return round(($imageInfo[0] * $imageInfo[1] * $imageInfo['bits'] * $imageInfo['channels'] / 8 + Pow(2, 16)) * 1.65);
    }

    /**
     * Get the image processor loading file from storage if needed
     *
     * @return Varien_Image
     */
    public function getImageProcessor() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::getImageProcessor();
        }

        if(!$this->_processor) {
            $this->_processor = new Varien_Image($this->_getFileFromStorage($this->getBaseFile()));
        }
        return parent::getImageProcessor();
    }

    /**
     * Save processed image file
     *
     * @return self
     */
    public function saveFile() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::saveFile();
        }

        $this->getImageProcessor()->save($this->_tmpName);
        $this->_getStorageModel()->moveFile($this->_tmpName, $this->getNewFile());
        $this->_tmpName = null;
        return $this;
    }

    /**
     * Clear product image cache directory
     */
    public function clearCache() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::clearCache();
        }

        $directory = Mage::getBaseDir('media') . DS.'catalog'.DS.'product'.DS.'cache'.DS;
        $this->_getStorageModel()->deleteDir($directory);
    }

    /**
     * Get file from media storage
     *
     * @param string
     * @return string
     */
    protected function _getFileFromStorage($file) {
        if(!$this->_tmpName) {
            $this->_tmpName = $this->_getStorageModel()->copyFileToTmp($file);
        }
        return $this->_tmpName;
    }
}
