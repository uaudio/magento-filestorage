<?php

/**
 * Wysiwyg Images model
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Cms_Wysiwyg_Images_Storage extends Mage_Cms_Model_Wysiwyg_Images_Storage {
    /**
     * @var storageModel
     */
    protected $_storageModel;

    /**
     * Thumbnail URL getter
     *
     * @param  string $filePath original file path
     * @param  boolean $checkFile OPTIONAL is it necessary to check file availability
     * @return string | false
     */
    public function getThumbnailUrl($filePath, $checkFile = false) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::getThumbnailUrl($filePath, $checkFile);
        }

        if(!$this->_getStorageModel()->fileExists($filePath)) {
            return false;
        }

        $dest = $this->resizeFile($filePath);
        if($dest) {
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$dest;
        }

        return false;
    }


    /**
     * Create thumbnail for image and save it to thumbnails directory
     *
     * @param string $source Image path to be resized
     * @param bool $keepRation Keep aspect ratio or not
     * @return bool|string Resized filepath or false if errors were occurred
     */
    public function resizeFile($source, $keepRatio = true) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::resizeFile($source, $keepRatio);
        }

        if(!$this->_getStorageModel()->fileExists($source)) {
            return false;
        }

        try {
            $targetFile = str_replace('//', '/', $this->getThumbsPath($source)) . DS . pathinfo($source, PATHINFO_BASENAME);
            if(!$this->_getStorageModel()->fileExists($targetFile)) {
                $tmpName = $this->_getStorageModel()->copyFileToTmp($source);
                $image = Varien_Image_Adapter::factory('GD2');
                $image->open($tmpName);
                $width = $this->getConfigData('resize_width');
                $height = $this->getConfigData('resize_height');
                $image->keepAspectRatio($keepRatio);
                $image->resize($width, $height);
                $image->save($tmpName);
                $dest = $this->_getStorageModel()->moveFile($tmpName, $targetFile);
                return $dest;
            } else {
                return $this->_getStorageModel()->getRelativeDestination($targetFile);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Create new directory in storage
     *
     * @param string $name New directory name
     * @param string $path Parent directory path
     * @throws Mage_Core_Exception
     * @return array New directory info
     */
    public function createDirectory($name, $path) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::createDirectory($name, $path);
        }

        if (!preg_match(self::DIRECTORY_NAME_REGEXP, $name)) {
            Mage::throwException(Mage::helper('cms')->__('Invalid folder name. Please, use alphanumeric characters, underscores and dashes.'));
        }

        $newPath = $path . DS . $name;
        if($this->_getStorageModel()->createDir($newPath)) {
            $result = [
                'name'          => $name,
                'short_name'    => $this->getHelper()->getShortFilename($name),
                'path'          => $newPath,
                'id'            => $this->getHelper()->convertPathToId($newPath)
            ];

            return $result;
        }
        Mage::throwException(Mage::helper('cms')->__('Cannot create new directory.'));
    }

    /**
     * Recursively delete directory from storage
     *
     * @param string $path Target dir
     * @return void
     */
    public function deleteDirectory($path) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::deleteDirectory($path);
        }

        // prevent accidental root directory deleting
        $rootCmp = rtrim($this->getHelper()->getStorageRoot(), DS);
        $pathCmp = rtrim($path, DS);

        if ($rootCmp == $pathCmp) {
            Mage::throwException(Mage::helper('cms')->__('Cannot delete root directory %s.', $path));
        }

        if(!$this->_getStorageModel()->deleteDir($path)) {
            Mage::throwException(Mage::helper('cms')->__('Cannot delete directory %s.', $path));
        }
        $this->_getStorageModel()->deleteDir($this->getThumbnailRoot() . DS . ltrim(substr($pathCmp, strlen($rootCmp)), '\\/'));
    }

    /**
     * Delete file (and its thumbnail if exists) from storage
     *
     * @param string $target File path to be deleted
     * @return Mage_Cms_Model_Wysiwyg_Images_Storage
     */
    public function deleteFile($target) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::deleteFile($target);
        }

        $thumb = str_replace('//', '/', $this->getThumbsPath($target)) . DS . pathinfo($target, PATHINFO_BASENAME);

        $this->_getStorageModel()->deleteFile($target);
        $this->_getStorageModel()->deleteFile($thumb);
        return $this;
    }

    /**
     * Get storage model
     *
     * @return Uaudio_Storage_Model_Storage_Abstract
     */
    protected function _getStorageModel() {
        if(!$this->_storageModel) {
            $this->_storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
        }
        return $this->_storageModel;
    }
}
