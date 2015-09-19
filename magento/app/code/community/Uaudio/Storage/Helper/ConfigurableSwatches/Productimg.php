<?php

/**
 * Configurable swatches images helper
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Helper_ConfigurableSwatches_Productimg extends Mage_ConfigurableSwatches_Helper_Productimg {

    /**
     * Cleans out the swatch image cache dir
     */
    public function clearSwatchesCache() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::clearSwatchesCache();
        }

        $directory = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . self::SWATCH_CACHE_DIR;
        Mage::getSingleton('core/file_storage')->getStorageModel()->deleteDir($directory);
    }

    /**
     * Performs the resize operation on the given swatch image file and returns a
     * relative path to the resulting image file
     *
     * @param string $filename
     * @param string $tag
     * @param int $width
     * @param int $height
     * @return string
     */
    protected function _resizeSwatchImage($filename, $tag, $width, $height) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::_resizeSwatchImage($filename, $tag, $width, $height);
        }

        // Form full path to where we want to cache resized version
        $destPathArr = array(
            self::SWATCH_CACHE_DIR,
            Mage::app()->getStore()->getId(),
            $width . 'x' . $height,
            $tag,
            trim($filename, '/'),
        );

        $destPath = implode('/', $destPathArr);

        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();

        // Check if cached image exists already

        $fullDest = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . $destPath;
        if(!$storageModel->fileExists($fullDest)) {
            // Check for source image
            if ($tag == 'product') {
                $sourceFilePath = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath() . $filename;
            } else {
                $sourceFilePath = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . self::SWATCH_FALLBACK_MEDIA_DIR . DS . $filename;
            }

            if (!$storageModel->fileExists($sourceFilePath)) {
                return false;
            }

            // Do resize and save
            $tmpFile = $storageModel->copyFiletoTmp($sourceFilePath);
            $processor = new Varien_Image($tmpFile);
            $processor->resize($width, $height);
            $processor->save($tmpFile);
            $storageModel->moveFile($tmpFile, $fullDest);
        }

        return $destPath;
    }
}
