<?php

/**
 * Core design package model
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Core_Design_Package extends Mage_Core_Model_Design_Package {

    /**
     * Remove all merged js/css files
     *
     * @return  bool
     */
    public function cleanMergedJsCss() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::cleanMergedJsCss();
        }
        $mediaDir = Mage::getBaseDir('media');
        try {
            Mage::getSingleton('core/file_storage')->getStorageModel()->deleteDir($mediaDir.DS.'js');
            Mage::getSingleton('core/file_storage')->getStorageModel()->deleteDir($mediaDir.DS.'css');
            Mage::getSingleton('core/file_storage')->getStorageModel()->deleteDir($mediaDir.DS.'css_secure');
            return true;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Merges files into one and saves it into DB (if DB file storage is on)
     *
     * @see Mage_Core_Helper_Data::mergeFiles()
     * @param array $srcFiles
     * @param string|bool $targetFile - file path to be written
     * @param bool $mustMerge
     * @param callback $beforeMergeCallback
     * @param array|string $extensionsFilter
     * @return bool|string
     */
    protected function _mergeFiles(array $srcFiles, $targetFile = false, $mustMerge = false, $beforeMergeCallback = null, $extensionsFilter = array()) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::_mergeFiles($srcFiles, $targetFile, $mustMerge, $beforeMergeCallback, $extensionsFilter);
        }
        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
        if($storageModel->fileExists($targetFile)) {
            $filemtime = $storageModel->getTimestamp($targetFile);
        } else {
            $filemtime = null;
        }

        $result = Mage::helper('core')->mergeFiles(
            $srcFiles,
            $targetFile,
            $mustMerge,
            $beforeMergeCallback,
            $extensionsFilter
        );

        if ($result && file_exists($targetFile) && (filemtime($targetFile) > $filemtime)) {
            $storageModel->moveFile($targetFile, $targetFile);
        }
        return $result;
    }
}
