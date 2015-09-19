<?php

/**
 * Filesystem items collection
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Storage_Collection extends Varien_Data_Collection_Filesystem {
    
    /**
     * @var Uaudio_Storage_Model_Storage_Abstract
     */
    protected $_storageModel;

    /**
     * Get files from specified directory recursively (if needed)
     *
     * @param string|array $dir
     */
    protected function _collectRecursive($dir) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            parent::_collectRecursive($dir);
            return;
        }
        $this->_fullResults = [];
        if (!is_array($dir)) {
            $dir = array($dir);
        }
        foreach ($dir as $folder) {
            $this->_fullResults = $this->_getStorageModel()->listContents($folder, $this->_collectRecursively);
        }
        if (empty($this->_fullResults)) {
            return;
        }

        foreach ($this->_fullResults as $i => $item) {
            $item['path'] = $this->_getStorageModel()->getLocalDestination($item['path']);
            $item['dirname'] = dirname($item['path']);

            $this->_fullResults[$item['path']] = $item;
            $this->_fullResults[$item['path']]['filename'] = $item['path'];
            if(isset($item['timestamp'])) $this->_fullResults[$item['path']]['mtime'] = $item['timestamp'];
            unset($this->_fullResults[$i]);

            if ($item['type'] == 'dir' && (!$this->_allowedDirsMask || preg_match($this->_allowedDirsMask, $item['basename']))) {
                if ($this->_collectDirs) {
                    if ($this->_dirsFirst) {
                        $this->_collectedDirs[] = $item['path'];
                    } else {
                        $this->_collectedFiles[] = $item['path'];
                    }
                }
            } else if ($this->_collectFiles && $item['type'] == 'file'
                && (!$this->_allowedFilesMask || preg_match($this->_allowedFilesMask, $item['basename']))
                && (!$this->_disallowedFilesMask || !preg_match($this->_disallowedFilesMask, $item['basename']))) {
                $this->_collectedFiles[] = $item['path'];
            }
        }
    }

    /**
     * Generate item row basing on the filename
     *
     * @param string $filename
     * @return array
     */
    protected function _generateRow($filename) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::_generateRow($filename);
        }

        preg_replace('~[/\\\]+~', DIRECTORY_SEPARATOR, $filename);
        return $this->_fullResults[$filename];
    }

    /**
     * Target directory setter. Adds directory to be scanned
     *
     * @param string
     * @return self
     */
    public function addTargetDir($value) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::addTargetDir($value);
        }

        $value = (string)$value;
        $this->_targetDirs[$value] = $value;
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
