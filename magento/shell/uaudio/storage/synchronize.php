<?php
chdir('/home/vagrant/Code/magento/htdocs/shell');
require_once('abstract.php');
ini_set('memory_limit', '512M');

class Uaudio_Storage_Synchronize extends Mage_Shell_Abstract {

    public function run() {
        $this->init();

        if($this->getArg('info')) {
            $this->info();
        } else if($type = $this->getArg('type')) {
            $storage = null;
            parse_str(str_replace(',', '&', $this->getArg('settings')), $settings);
            foreach($this->storageOptions as $option) {
                if($option['type'] == $type) {
                    if($type == 'file_storage_database') {
                        $storage = [
                            'type' => $option['index'],
                            'connection' => $settings['connection_name']
                        ];
                    } else {
                        $storage = [
                            'type' => $option['index'],
                            'settings' => $settings,
                        ];
                    }
                }
            }
            if(!$storage) {
                echo $this->usageHelp();
            } else {
                Mage::getModel('core/file_storage')->synchronize($storage, $this->getArg('c') || $this->getArg('clear'), $this->getArg('v') || $this->getArg('verbose'));
            }
        } else {
            echo $this->usageHelp();
        }
    }

    public function info() {
        foreach($this->storageOptions as $indx => $option) {
            echo sprintf(" - %s:\n", $option['type']);
            switch($indx) {
                case 0:
                    echo "\tnone\n";
                    break;
                case 1:
                    echo "\tconnection_name=<db_connection>\n";
                    break;
                default:
                    $model = Mage::getModel($option['model']);
                    $settings = $model->settings();
                    foreach($settings as $key => $label) {
                        echo sprintf("\t%s=<%s>\n", $key, $label);
                    }
            }
            echo "\n";
        }
    }

    protected function init() {
        $this->storageOptions = Mage::helper('uaudio_storage')->getStorageOptions();
        $this->storageOptions[0] = [
            'index' => 0,
            'label' => 'Core File System',
            'model' => 'core/file_storage_file',
            'class' => 'Mage_Core_Model_File_Storage_File',
        ];

        $this->storageOptions[1] = [
            'index' => 1,
            'label' => 'Core Database',
            'model' => 'core/file_storage_database',
            'class' => 'Mage_Core_Model_File_Storage_Database',
        ];
        ksort($this->storageOptions);

        foreach($this->storageOptions as $indx => $option) {
            $this->storageOptions[$indx]['type'] = basename($option['model']);
            $this->optionTypes[] = basename($option['model']);
        }
    }

    public function usageHelp() {
        $types = implode("|", $this->optionTypes);
        return <<<USAGE
Usage:  php -f synchronize.php -- [options]

  --type <$types>                               Storage option to synchronize to
  --settings "setting1=foo,setting2=bar"        Comma separated Settings
  --clear|-c                                    Clear Destination
  --verbose|-v                                  Show files being transferred
  info                                          Show required settings for storage type
  help                                          This help

USAGE;
    }
}

$storage = new Uaudio_Storage_Synchronize();
$storage->run();
