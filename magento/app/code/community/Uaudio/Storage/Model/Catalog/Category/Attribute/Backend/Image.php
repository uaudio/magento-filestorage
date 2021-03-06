<?php

/**
 * Catalog category image attribute backend model
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Catalog_Category_Attribute_Backend_Image extends Mage_Catalog_Model_Category_Attribute_Backend_Image {

    /**
     * Save uploaded file and set its name to category
     *
     * @param Varien_Object $object
     */
    public function afterSave($object) {
        if(!Mage::helper('uaudio_storage')->isEnabled()) {
            return parent::afterSave($object);
        }

        $value = $object->getData($this->getAttribute()->getName());
        $path = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'category' . DS;

        if (is_array($value) && !empty($value['delete'])) {
            $model = Mage::getModel('core/file_storage')->getStorageModel();
            $model->deleteFile($path.$value['value']);

            $object->setData($this->getAttribute()->getName(), '');
            $this->getAttribute()->getEntity()->saveAttribute($object, $this->getAttribute()->getName());
        }

        try {
            $uploader = new Mage_Core_Model_File_Uploader($this->getAttribute()->getName());
            $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
            $uploader->setAllowRenameFiles(true);

            if(class_exists('Mage_Core_Model_File_Validator_Image')) {
                // added for mage patch SUPEE-7405
                $uploader->addValidateCallback(
                    Mage_Core_Model_File_Validator_Image::NAME,
                    new Mage_Core_Model_File_Validator_Image(),
                    "validate"
                );
            }
            $result = $uploader->save($path);

            $object->setData($this->getAttribute()->getName(), $result['file']);
            $this->getAttribute()->getEntity()->saveAttribute($object, $this->getAttribute()->getName());
        } catch (Exception $e) {
            if ($e->getCode() != Mage_Core_Model_File_Uploader::TMP_NAME_EMPTY) {
                Mage::logException($e);
            }
            return null;
        }
    }
}
