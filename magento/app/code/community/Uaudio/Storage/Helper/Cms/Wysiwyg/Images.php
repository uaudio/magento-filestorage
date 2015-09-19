<?php

/**
 * Wysiwyg Images Helper
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Helper_Cms_Wysiwyg_Images extends Mage_Cms_Helper_Wysiwyg_Images {

    /**
     * Images Storage root directory
     *
     * @return string
     */
    public function getStorageRoot() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::getStorageRoot();
        }

        if (!$this->_storageRoot) {
            $this->_storageRoot = Mage::getConfig()->getOptions()->getMediaDir() . DS . Mage_Cms_Model_Wysiwyg_Config::IMAGE_DIRECTORY . DS;
        }
        return $this->_storageRoot;
    }

    /**
     * Encode path to HTML element id
     *
     * @param string $path Path to file/directory
     * @return string
     */
    public function convertPathToId($path) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::convertPathToId($path);
        }
        $path = str_replace($this->getStorageRoot(), '', $path);
        return $this->idEncode($path);
    }

    /**
     * Decode HTML element id
     *
     * @param string $id
     * @return string
     */
    public function convertIdToPath($id) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::convertIdToPath($id);
        }

        $path = $this->idDecode($id);
        $storageRoot = $this->getStorageRoot();
        if (!strstr($path, $storageRoot)) {
            $path = $storageRoot . $path;
        }
        return $path;
    }

    /**
     * Revert opration to idEncode
     *
     * @param string $string
     * @return string
     */
    public function idDecode($string) {
        if($string == 'root') $string = '';
        $string = strtr($string, ':_-', '+/=');
        return base64_decode($string);
    }

    /**
     * Return path of the current selected directory or root directory for startup
     * Try to create target directory if it doesn't exist
     *
     * @throws Mage_Core_Exception
     * @return string
     */
    public function getCurrentPath() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::getCurrentPath();
        }

        if (!$this->_currentPath) {
            $currentPath = $this->getStorageRoot();
                
            $node = $this->_getRequest()->getParam($this->getTreeNodeName());
            if ($node) {
                $this->_currentPath = $this->convertIdToPath($node);
            }
        }
        return $this->_currentPath;
    }

    /**
     * Return URL based on current selected directory or root directory for startup
     *
     * @return string
     */
    public function getCurrentUrl() {
        if (!$this->_currentUrl) {
            $mediaPath = Mage::getConfig()->getOptions()->getMediaDir();
            $path = str_replace($mediaPath, '', $this->getCurrentPath());
            $path = trim($path, DS);
            $this->_currentUrl = Mage::app()->getStore($this->_storeId)->getBaseUrl('media') .  $this->convertPathToUrl($path) . '/';
        }
        return $this->_currentUrl;

    }
}
