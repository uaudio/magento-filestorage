<?php
require_once(Mage::getModuleDir('controllers', 'Mage_Adminhtml').DS.'Cms'.DS.'WysiwygController.php');


class Uaudio_Storage_Adminhtml_Cms_WysiwygController extends Mage_Adminhtml_Cms_WysiwygController {
    public function directiveAction() {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::directiveAction();
        }
        $storageModel = Mage::getSingleton('core/file_storage')->getStorageModel();

        $directive = $this->getRequest()->getParam('___directive');
        $directive = Mage::helper('core')->urlDecode($directive);
        $url = Mage::getModel('cms/adminhtml_template_filter')->filter($directive);
        if($storageModel->fileExists($url)) {
            $url = ltrim(str_replace(Mage::getBaseDir('media'), '', $url), '/');
            $this->getResponse()->setRedirect(Mage::getBaseUrl('media').$url);
        } else {
            $image = Varien_Image_Adapter::factory('GD2');
            $image->open(Mage::getSingleton('cms/wysiwyg_config')->getSkinImagePlaceholderPath());
            $image->display();
        }
    }
}
