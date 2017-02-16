<?php

/**
 * Catalog product media gallery attribute backend model
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Catalog_Product_Attribute_Backend_Media extends Mage_Catalog_Model_Product_Attribute_Backend_Media {

    /**
     * Move image from temporary directory to file storage
     *
     * @param string $file
     * @return string
     */
    protected function _moveImageFromTmp($file) { 
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::_moveImageFromTmp($file);
        }

        if (strrpos($file, '.tmp') == strlen($file)-4) {
            $file = substr($file, 0, strlen($file)-4);
        }

        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
        $storageModel->setAllowRenameFiles(true);

        $destFile = $storageModel->renameFile($this->_getConfig()->getTmpMediaPath($file), $this->_getConfig()->getMediaPath($file));

        return str_replace($this->_getConfig()->getBaseMediaPathAddition(), '', ltrim($destFile, '/'));
    }

    /**
     * Add image to media gallery and return new filename
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string                     $file              file path of image in file system
     * @param string|array               $mediaAttribute    code of attribute with type 'media_image',
     *                                                      leave blank if image should be only in gallery
     * @param boolean                    $move              if true, it will move source file
     * @param boolean                    $exclude           mark image as disabled in product page view
     * @return string
     */
    public function addImage(Mage_Catalog_Model_Product $product, $file, $mediaAttribute = null, $move = false, $exclude = true) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::addImage(product, $file, $mediaAttribute, $move, $exclude);
        }

        $file = realpath($file);

        if (!$file || !file_exists($file)) {
            Mage::throwException(Mage::helper('catalog')->__('Image does not exist.'));
        }

        Mage::dispatchEvent('catalog_product_media_add_image', array('product' => $product, 'image' => $file));

        $pathinfo = pathinfo($file);
        $imgExtensions = array('jpg','jpeg','gif','png');
        if (!isset($pathinfo['extension']) || !in_array(strtolower($pathinfo['extension']), $imgExtensions)) {
            Mage::throwException(Mage::helper('catalog')->__('Invalid image file type.'));
        }

        $fileName       = Mage_Core_Model_File_Uploader::getCorrectFileName($pathinfo['basename']);
        $dispretionPath = Mage_Core_Model_File_Uploader::getDispretionPath($fileName);
        $fileName       = $dispretionPath . DS . $fileName;
        $dest           = $this->_getConfig()->getTmpMediaPath($fileName);

        $storageModel   = Mage::getSingleton('core/file_storage')->getStorageModel();
        $storageModel->setAllowRenameFiles(true);

        try {
            if($move) {
                $uploadDestination = $storageModel->moveFile($file, $dest);
            } else {
                $uploadDestination = $storageModel->moveUploadFile($file, $dest);
            }
            if(!$uploadDestination) throw new Exception();

            $fileName = basename($uploadDestination);
            $dispretionPath = Mage_Core_Model_File_Uploader::getDispretionPath($fileName);
            $fileName       = $dispretionPath . DS . $fileName;
        }
        catch (Exception $e) {
            Mage::throwException(Mage::helper('catalog')->__('Failed to move file: %s', $e->getMessage()));
        }

        $fileName = str_replace(DS, '/', $fileName);

        $attrCode = $this->getAttribute()->getAttributeCode();
        $mediaGalleryData = $product->getData($attrCode);
        $position = 0;
        if (!is_array($mediaGalleryData)) {
            $mediaGalleryData = array(
                'images' => array()
            );
        }

        foreach ($mediaGalleryData['images'] as &$image) {
            if (isset($image['position']) && $image['position'] > $position) {
                $position = $image['position'];
            }
        }

        $position++;
        $mediaGalleryData['images'][] = array(
            'file'     => $fileName,
            'position' => $position,
            'label'    => '',
            'disabled' => (int) $exclude
        );

        $product->setData($attrCode, $mediaGalleryData);

        if (!is_null($mediaAttribute)) {
            $this->setMediaAttribute($product, $mediaAttribute, $fileName);
        }

        return $fileName;
    }

    /**
     * Copy image and return new filename.
     *
     * @param string $file
     * @return string
     */
    protected function _copyImage($file) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::_copyImage($file);
        }

        try {
            $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
            $filePath = $this->_getConfig()->getMediaPath($file);

            if(!$storageModel->fileExists($filePath)) {
                throw new Exception();
            }

            $destFile = $storageModel->getLocalDestination($storageModel->copyFile($filePath, $filePath));

        } catch (Exception $e) {
            $file = $this->_getConfig()->getMediaPath($file);
            Mage::throwException(
                Mage::helper('catalog')->__('Failed to copy file %s. Please, delete media with non-existing images and try again.', $file)
            );
        }

        return $destFile;
    }
}
