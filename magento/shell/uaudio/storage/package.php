<?php
//chdir('../..');
chdir('/home/vagrant/Code/magento/htdocs/shell');
require_once('abstract.php');

class Uaudio_Storage_Package extends Mage_Shell_Abstract {
    public function run() {
        chdir(Mage::getBaseDir());
        $version = (string)Mage::getConfig()->getModuleConfig("Uaudio_Storage")->version;
        $package = array (
          'file_name' => 'magento_filestorage',
          'name' => 'magento_filestorage',
          'channel' => 'community',
          'version_ids' => 
          array (
            0 => '2',
          ),
          'summary' => 'Magento File Storage is an extension that lets you use different storage backends for your magento media.',
          'description' => 'Magento File Storage is an extension that lets you use different storage backends for your magento media.',
          'license' => 'OSL',
          'license_uri' => '',
          'version' => $version,
          'stability' => 'beta',
          'notes' => 'After installing the module go to app/code/community/Uaudio and run composer install.',
          'authors' => 
          array (
            'name' => 
            array (
              0 => 'Universal Audio',
            ),
            'user' => 
            array (
              0 => 'cloud',
            ),
            'email' => 
            array (
              0 => 'cloud@uaudio.com',
            ),
          ),
          'depends_php_min' => '5.5.0.',
          'depends_php_max' => '5.6.0.',
          'depends' => 
          array (
            'package' => 
            array (
              'name' => 
              array (
                0 => '',
              ),
              'channel' => 
              array (
                0 => '',
              ),
              'min' => 
              array (
                0 => '',
              ),
              'max' => 
              array (
                0 => '',
              ),
              'files' => 
              array (
                0 => '   ',
              ),
            ),
            'extension' => 
            array (
              'name' => 
              array (
                0 => 'Core',
              ),
              'min' => 
              array (
                0 => '',
              ),
              'max' => 
              array (
                0 => '',
              ),
            ),
          ),
          'contents' => 
          array (
            'target' => 
            array (
              0 => 'magelocal',
              1 => 'magecommunity',
              2 => 'magecommunity',
              3 => 'mage',
              4 => 'magecommunity',
              5 => 'mage',
            ),
            'path' => 
            array (
              0 => '',
              1 => 'Uaudio/Storage',
              2 => 'Mage/Core/Model/File/Uploader.php',
              3 => 'app/etc/modules/Uaudio_Storage.xml',
              4 => 'Uaudio/composer.json',
              5 => 'shell/uaudio/storage',
            ),
            'type' => 
            array (
              0 => 'file',
              1 => 'dir',
              2 => 'file',
              3 => 'file',
              4 => 'file',
              5 => 'dir',
            ),
            'include' => 
            array (
              0 => '',
              1 => '',
              2 => '',
              3 => '',
              4 => '',
              5 => '',
            ),
            'ignore' => 
            array (
              0 => '',
              1 => '',
              2 => '',
              3 => '',
              4 => '',
              5 => '',
            ),
          ),
        );
        try {
            $ext = Mage::getModel('connect/extension');
            $ext->setData($package);
            if ($ext->savePackage()) {
                $ext->createPackage();
                exec('mv var/connect/*.tgz ./');
                echo "Package built\n";
            } else {
                throw new Exception('There was an error saving.');
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }
}

$package = new Uaudio_Storage_Package();
$package->run();
