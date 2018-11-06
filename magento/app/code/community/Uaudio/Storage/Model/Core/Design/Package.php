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
        if($storageModel->fileExists($targetFile) && !$mustMerge) {
            return true;
        }

        $result = Mage::helper('core')->mergeFiles(
            $srcFiles,
            $targetFile,
            $mustMerge,
            $beforeMergeCallback,
            $extensionsFilter
        );

        if ($result) {
            $size = filesize($targetFile);
            $storageModel->moveFile($targetFile, $targetFile);
            if($size != $storageModel->getSize($targetFile)) {
                $storageModel->deleteFile($targetFile);
                Mage::throwException(sprintf("File size does not match [file: %s]", $storageModel->getRelativeDestination($targetFile)));
            }
        }
        return $result;
    }

    /**
     * Merge specified javascript files and return URL to the merged file on success
     *
     * @param $files
     * @return string
     */
    public function getMergedJsUrl($files) {
        $string = null;
        foreach($files as $file) {
            $string .= md5_file($file);
        }
        $targetFilename = md5($string) . '.js';
        $targetDir = $this->_initMergerDir('js');
        if (!$targetDir) {
            return '';
        }
        if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, null, 'js')) {
            return Mage::getBaseUrl('media', Mage::app()->getRequest()->isSecure()) . 'js/' . $targetFilename;
        }
        return '';
    }

    /**
     * Merge specified css files and return URL to the merged file on success
     *
     * @param $files
     * @return string
     */
    public function getMergedCssUrl($files) {
        // secure or unsecure
        $isSecure = Mage::app()->getRequest()->isSecure();
        $mergerDir = $isSecure ? 'css_secure' : 'css';
        $targetDir = $this->_initMergerDir($mergerDir);
        if (!$targetDir) {
            return '';
        }

        // base hostname & port
        $baseMediaUrl = Mage::getBaseUrl('media', $isSecure);
        $hostname = parse_url($baseMediaUrl, PHP_URL_HOST);
        $port = parse_url($baseMediaUrl, PHP_URL_PORT);
        if (false === $port) {
            $port = $isSecure ? 443 : 80;
        }

        // merge into target file
        $string = null;
        foreach($files as $file) {
            if (file_exists($file)) $string .= md5_file($file);
        }
        $targetFilename = md5($string."|{$hostname}|{$port}").'.css';

        $mergeFilesResult = $this->_mergeFiles(
            $files, $targetDir . DS . $targetFilename,
            false,
            array($this, 'beforeMergeCss'),
            'css'
        );
        if ($mergeFilesResult) {
            return $baseMediaUrl . $mergerDir . '/' . $targetFilename;
        }
        return '';
    }

}
