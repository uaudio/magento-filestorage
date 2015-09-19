<?php

/**
 * Captcha Observer
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Captcha_Observer extends Mage_Captcha_Model_Observer {

    /**
     * Delete Expired Captcha Images
     *
     * @return self
     */
    public function deleteExpiredImages() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::deleteExpiredImages();
        }
        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();

        foreach (Mage::app()->getWebsites(true) as $website){
            $expire = time() - Mage::helper('captcha')->getConfigNode('timeout', $website->getDefaultStore())*60;
            $imageDirectory = Mage::helper('captcha')->getImgDir($website);
            foreach($storageModel->listContents($imageDirectory) as $file) {
                if ($file['type']=='file' && $file['extension'] == 'png') {
                    if ($file['timestamp'] < $expire) {
                        $storageModel->deleteFile($file['path']);
                    }
                }
            }
        }
        return $this;
    }
}
