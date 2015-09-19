<?php

/**
 * File storage model class
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Storage extends Mage_Core_Model_File_Storage {

    const XML_CONFIG_PATH = 'uaudio_storage';

    /**
     * Retrieve storage model
     * If storage not defined - retrieve current storage
     *
     * params = array(
     *  connection  => string,  - define connection for model if needed
     *  init        => bool     - force initialization process for storage model
     *  settings    => array    - configuration for storage modules
     * )
     *
     * @param  int|null $storage
     * @param  array $params
     * @return Mage_Core_Model_File_Storage_Abstract | false
     */
    public function getStorageModel($storage = null, $params = array()) {
        if (is_null($storage)) {
            $storage = Mage::helper('core/file_storage')->getCurrentStorageCode();
        }
        $model = false;

        if($storage == self::STORAGE_MEDIA_FILE_SYSTEM || $storage == self::STORAGE_MEDIA_DATABASE) {
            return parent::getStorageModel($storage, $params);
        }

        $options = Mage::getConfig()->getNode(self::XML_CONFIG_PATH);
        if(!isset($params['settings'])) $params['settings'] = [];

        $options = Mage::helper('uaudio_storage')->getStorageOptions();
        if(isset($options[$storage])) {
            $model = Mage::getSingleton($options[$storage]['model'], $params['settings']);
        }

        if(!$model) return false;

        if (isset($params['init']) && $params['init']) {
            $model->init();
        }

        return $model;
    }

    /**
     * Synchronize current media storage with defined
     * $storage = array(
     *  type        => int
     *  connection  => string
     * )
     *
     * @param  array $storage
     * @param  bool
     * @param  bool
     * @return self
     */
    public function synchronize($storage, $clear=false, $verbose=false) {

        if (is_array($storage) && isset($storage['type'])) {
            $storageDest    = (int) $storage['type'];
            $connection     = (isset($storage['connection'])) ? $storage['connection'] : null;
            $helper         = Mage::helper('core/file_storage');

            // if unable to sync to internal storage from itself
            if ($storageDest == $helper->getCurrentStorageCode() && $helper->isInternalStorage()) {
                return $this;
            }

            $sourceModel        = $this->getStorageModel();
            $destinationModel   = $this->getStorageModel($storageDest, ['connection' => $connection, 'init' => true, 'settings' => $storage['settings']]);
            $destinationModel->setNoCache(true);
            $destinationModel->setVerbose($verbose);

            if (!$sourceModel || !$destinationModel) {
                return $this;
            }

            $hasErrors = false;
            $flag = $this->getSyncFlag();
            $flagData = array(
                'source'                        => $sourceModel->getStorageName(),
                'destination'                   => $destinationModel->getStorageName(),
                'destination_storage_type'      => $storageDest,
                'destination_connection_name'   => (string) $destinationModel->getConfigConnectionName(),
                'has_errors'                    => false,
                'timeout_reached'               => false
            );
            $flag->setFlagData($flagData);

            if($clear) {
                $destinationModel->clear();
            }

            $offset = 0;
            while (($dirs = $sourceModel->exportDirectories($offset)) !== false) {
                $flagData['timeout_reached'] = false;
                if (!$hasErrors) {
                    $hasErrors = $this->_synchronizeHasErrors($sourceModel, $destinationModel);
                    if ($hasErrors) {
                        $flagData['has_errors'] = true;
                    }
                }

                $flag->setFlagData($flagData)->save();

                $destinationModel->importDirectories($dirs);
                $offset += count($dirs);
            }
            unset($dirs);

            $offset = 0;
            while (($files = $sourceModel->exportFiles($offset, 1)) !== false) {
                $flagData['timeout_reached'] = false;
                if (!$hasErrors) {
                    $hasErrors = $this->_synchronizeHasErrors($sourceModel, $destinationModel);
                    if ($hasErrors) {
                        $flagData['has_errors'] = true;
                    }
                }

                $flag->setFlagData($flagData)
                    ->save();

                $destinationModel->importFiles($files);
                $offset += count($files);
            }
            unset($files);
        }

        return $this;
    }
}
