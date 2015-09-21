<?php

/**
 * Generate options for media storage selection
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Mage_Core_Model_File_Uploader extends Varien_File_Uploader {

    /**
     * Flag, that defines should DB processing be skipped
     *
     * @var bool
     */
    protected $_skipDbProcessing = false;

    /**
     * Used to save uploaded file into destination folder with
     * original or new file name (if specified)
     *
     * @param string $destinationFolder
     * @param string $newFileName
     * @access public
     * @return void|bool
     */
    public function save($destinationFolder, $newFileName = null) {
        
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::save($destinationFolder, $newFileName);
        }
        
        $this->_validateFile();
        $this->_result = false;

        $destinationFile = $destinationFolder;
        $fileName = isset($newFileName) ? $newFileName : $this->_file['name'];
        $fileName = self::getCorrectFileName($fileName);
        if ($this->_enableFilesDispersion) {
            $fileName = $this->correctFileNameCase($fileName);
            $this->setAllowCreateFolders(true);
            $this->_dispretionPath = self::getDispretionPath($fileName);
            $destinationFile .= $this->_dispretionPath;
        }

        $destinationFile = self::_addDirSeparator($destinationFile) . $fileName;
        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();
        $storageModel->setAllowRenameFiles($this->_allowRenameFiles);

        $uploadDestination = $storageModel->moveUploadFile($this->_file['tmp_name'], $destinationFile);
        $uploadDestination = str_replace('//', '/', $uploadDestination);
        if($uploadDestination) {
            $fileName = basename($uploadDestination);
            $path = dirname($uploadDestination);
            if ($this->_enableFilesDispersion) {
                $fileName = str_replace(DIRECTORY_SEPARATOR, '/', self::_addDirSeparator($this->_dispretionPath)) . basename($uploadDestination);
                $path = str_replace($fileName, '', $uploadDestination);
            }

            $this->_uploadedFileName = $fileName;
            $this->_uploadedFileDir = $path;
            $this->_result = $this->_file;
            $this->_result['path'] = $path;
            $this->_result['file'] = $fileName;
            $this->_afterSave($this->_result);
        }
        return $this->_result;
    }

    /**
     * Save file to storage
     *
     * @param  array $result
     * @return Mage_Core_Model_File_Uploader
     */
    protected function _afterSave($result) {
        if (empty($result['path']) || empty($result['file'])) {
            return $this;
        }

        /** @var $helper Mage_Core_Helper_File_Storage */
        $helper = Mage::helper('core/file_storage');

        if ($helper->isInternalStorage() || $this->skipDbProcessing()) {
            return $this;
        }

        /** @var $dbHelper Mage_Core_Helper_File_Storage_Database */
        $dbHelper = Mage::helper('core/file_storage_database');
        $this->_result['file'] = $dbHelper->saveUploadedFile($result);

        return $this;
    }

    /**
     * Getter/Setter for _skipDbProcessing flag
     *
     * @param null|bool $flag
     * @return bool|Mage_Core_Model_File_Uploader
     */
    public function skipDbProcessing($flag = null) {
        if (is_null($flag)) {
            return $this->_skipDbProcessing;
        }
        $this->_skipDbProcessing = (bool)$flag;
        return $this;
    }

    /**
     * Check protected/allowed extension
     *
     * @param string $extension
     * @return boolean
     */
    public function checkAllowedExtension($extension) {
        //validate with protected file types
        /** @var $validator Mage_Core_Model_File_Validator_NotProtectedExtension */
        $validator = Mage::getSingleton('core/file_validator_notProtectedExtension');
        if (!$validator->isValid($extension)) {
            return false;
        }

        return parent::checkAllowedExtension($extension);
    }
}
