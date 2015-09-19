<?php

/**
 * Implementation of Zend_Captcha
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Captcha_Zend extends Mage_Captcha_Model_Zend {

    /**
     * Generate captcha
     *
     * @return string
     */
    public function generate() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::generate();
        }
        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();

        $id = Zend_Captcha_Word::generate();
        $tries = 5;
        // If there's already such file, try creating a new ID
        while($tries-- && $storageModel->fileExists($this->getImgDir() . $id . $this->getSuffix())) {
            $id = $this->_generateRandomId();
            $this->_setId($id);
        }
        $this->_generateImage($id, $this->getWord());

        if (mt_rand(1, $this->getGcFreq()) == 1) {
            $this->_gc();
        }
        $imgFile = $this->getImgDir() . $id . $this->getSuffix();
        $storageModel->moveFile($imgFile, $imgFile);
        return $id;
    }
}
