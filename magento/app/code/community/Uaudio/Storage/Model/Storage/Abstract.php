<?php
require_once('vendor/autoload.php');
use League\Flysystem\Filesystem;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memory as CacheStore;

/**
 * Abstract file storage model class
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
abstract class Uaudio_Storage_Model_Storage_Abstract extends Mage_Core_Model_File_Storage_Abstract {

    /**
     * @var League\Flysystem\AdapterInterface
     */
    protected $_adapter = null;

    /**
     * @var League\Flysystem\Filesystem
     */
    protected $_filesystem = null;

    /**
     * @var League\Flysystem\Cached\CacheInterface
     */
    protected $_cache = null;

    /**
     * @var prepended folder in storage
     */
    protected $_folder;

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * @var array
     */
    protected $_mediaDir;

    /**
     * Name for storage module
     *
     * @return string
     */
    public function getStorageName() {
        $options = Mage::helper('uaudio_storage')->getStorageOptions();
        return $options[$this::STORAGE_MEDIA_ID]['label'];
    }

    /**
     * Get the flysystem adapter
     *
     * @return League\Flysystem\AdapterInterface
     */
    abstract protected function _getAdapter();

    /**
     * Get the settings for this storage type
     *
     * @return array
     */
    abstract public function settings();

    /**
     * Constructor
     *
     * @return self
     */
    public function _construct() {
        parent::_construct();
        $this->_mediaDir = [
            Mage::getBaseDir('media'),
            realpath(Mage::getBaseDir('media')),
        ];
        return $this;
    }

    /**
     * Get flysystem object
     *
     * @return League\Flysystem\Filesystem
     */
    protected function _getFilesystem() {
        if(!$this->_filesystem) {
            try {
                if(Mage::app()->useCache('file_storage') && ($cache = $this->_getCache()) && !$this->getNoCache()) {
                    $adapter = new CachedAdapter($this->_getAdapter(), $cache);
                } else {
                    $cacheStore = new CacheStore();
                    $adapter = new CachedAdapter($this->_getAdapter(), $cacheStore);
                }
            } catch (Exception $e) {
                // catch adapter errors and default to using local adapter with media directory
                Mage::logException($e);
                $this->_adapter = new \League\Flysystem\Adapter\Local(Mage::getBaseDir('media'));
                $adapter = $this->_adapter;
            }
            $this->_filesystem = new Filesystem($adapter);
            $this->_filesystem->addPlugin(new League\Flysystem\Plugin\GetWithMetadata());
        }
        return $this->_filesystem;
    }

    /**
     * Get cache object if configured
     *
     * @return League\Flysystem\Cached\CacheInterface | false
     */
    protected function _getCache() {
        if($this->_cache === null) {
            $this->_cache = false;
            $options = Mage::getConfig()->getNode('global/cache');
            $options = $options ? $options->asArray() : [];


            if (isset($options['backend'])) {
                switch($options['backend']) {
                    case 'Cm_Cache_Backend_Redis':
                        $this->_cache = new Uaudio_Storage_Model_Storage_Cache_Redis();
                        $this->_cache->setAutosave(false);
                }
            }
        }
        return $this->_cache;
    }

    /**
     * Clear file metadata cache
     *
     * @return self
     */
    public function clearCache() {
        if($cache = $this->_getCache()) {
            $this->_getCache()->flush();
        }
        return $this;
    }

    /**
     * Initialization
     *
     * @return self
     */
    public function init() {
        return $this;
    }

    /**
     * Get the relative path to the media directory for this module
     *
     * @param string
     * @return string
     */
    public function getRelativeDestination($path) {
        return ltrim(str_replace($this->_mediaDir, '', $path), DS);
    }

    /**
     * Get the local location of a file in storage
     *
     * @param string
     * @return string
     */
    public function getLocalDestination($relativePath) {
        return $this->_mediaDir[0] . DS . ltrim($relativePath, DS);
    }

    /**
     * Delete file from storage
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile($path) {
        return $this->_getFilesystem()->delete($this->getRelativeDestination($path));
    }

    /**
     * Delete directory from storage
     *
     * @param string
     * @return bool
     */
    public function deleteDir($path) {
        return $this->_getFilesystem()->deleteDir($this->getRelativeDestination($path));
    }

    /**
     * Get timestamp for a file
     *
     * @param string
     * @return int
     */
    public function getTimestamp($file) {
        return $this->_getFilesystem()->getTimestamp($this->getRelativeDestination($file));
    }

    /**
     * Get filesize for a file
     *
     * @param string
     * @return int
     */
    public function getSize($file) {
        return $this->_getFilesystem()->getSize($this->getRelativeDestination($file));
    }

    /**
     * Get mimetype for a file
     *
     * @param string
     * @return string
     */
    public function getMimetype($file) {
        return $this->_getFilesystem()->getMimetype($this->getRelativeDestination($file));
    }

    public function updateMetadata($file, $metadata) {
        return $this;
    }

    /**
     * Get metadata for a file
     *
     * @param string
     * @return array
     */
    public function getMetadata($file) {
        if($this->isInMedia($file) && $this->fileExists($file)) {
            return $this->_getFilesystem()->getWithMetadata($this->getRelativeDestination($file), []);
        } else {
            return [];
        }
    }

    /**
     * Move an upload file to storage
     *
     * @param string
     * @param string
     * @return string|false
     */
    public function moveUploadFile($uploadFile, $destinationFile) {
        $destinationFile = $this->getRelativeDestination($destinationFile);
        if($this->getAllowRenameFiles()) {
            $destinationFile = $this->_getNewDestinationFile($destinationFile);
        }
        $stream = fopen($uploadFile, 'r+');
        $result = $this->_getFilesystem()->putStream($destinationFile, $stream);
        if(!$result) {
            Mage::logException(new Exception("Error uploading file $destinationFile"));
        }
        if(is_resource($stream)) {
            fclose($stream); // this is creating a not valid stream resource error somehow?
        }
        return $result ? $destinationFile : false;
    }

    /**
     * Move a local file to storage and remove local file
     *
     * @param string
     * @param string
     * @param string|false
     */
    public function moveFile($localFile, $destinationFile) {
        $destinationFile = $this->moveUploadFile($localFile, $destinationFile);
        unlink($localFile);
        return $destinationFile;
    }

    /**
     * Check whether file exists in media storage or local filesystem if not in media directory
     *
     * @param  string $filePath
     * @return bool
     */
    public function fileExists($filePath) {
        if($this->isInMedia($filePath)) {
            return $this->_getFilesystem()->has($this->getRelativeDestination($filePath));
        } else {
            return file_exists($filePath);
        }
    }

    /**
     * Check if requested file is in the media directory
     *
     * @param string
     * @return bool
     */
    public function isInMedia($filePath) {
        return (($filePath[0] == DS && (strstr($filePath, $this->_mediaDir[0]) || strstr($filePath, $this->_mediaDir[1]))) || $filePath[0] != DS);
    }

    /**
     * Rename file in storage
     *
     * @param  string $oldFilePath
     * @param  string $newFilePath
     * @return string
     */
    public function renameFile($oldFilePath, $newFilePath) {

        $oldFilePath = $this->getRelativeDestination($oldFilePath);
        $newFilePath = $this->getRelativeDestination($newFilePath);

        if($this->getAllowRenameFiles()) {
            $newFilePath = $this->_getNewDestinationFile($newFilePath);
        }

        $this->_getFilesystem()->rename($oldFilePath, $newFilePath);

        return $newFilePath;
    }

    /**
     * Read file stream
     *
     * @param string
     * @return resource
     */
    public function readStream($filePath) {
        return $this->_getFilesystem()->readStream($this->getRelativeDestination($filePath));
    }

    /**
     * Read a files contents
     *
     * @param string
     * @return string
     */
    public function read($filePath) {
        return $this->_getFilesystem()->read($this->getRelativeDestination($filePath));
    }

    /**
     * Write contents to a file
     *
     * @param string
     * @param string
     * @return boolean
     */
    public function put($filePath, $contents) {
        return $this->_getFilesystem()->put($this->getRelativeDestination($filePath), $contents);
    }

    /**
     * Copy files in storage
     *
     * @param  string $oldFilePath
     * @param  string $newFilePath
     * @return string | false - new file name
     */
    public function copyFile($oldFilePath, $newFilePath) {
        $oldFile = $this->getRelativeDestination($oldFilePath);
        $newFile = $this->_getNewDestinationFile($this->getRelativeDestination($newFilePath));
        if($this->_getFilesystem()->copy($oldFile, $newFile)) {
            return $newFile;
        } else {
            return false;
        }
    }

    /**
     * Store file into storage keeping local version
     *
     * @param  string
     * @return string|false
     */
    public function saveFile($filename) {
        return $this->moveUploadFile($filename, $filename);
    }

    /**
     * Copy file from storage to local system tmp directory
     *
     * @param string
     * @return string
     */
    public function copyFileToTmp($file) {
        if($this->fileExists($file)) {
            $tmpName = tempnam(sys_get_temp_dir(), 'uaudio_storage_');
            if($this->isInMedia($file)) {
                $fileStream = $this->readStream($file);
                $fp = fopen($tmpName, 'w+');
                stream_copy_to_stream($fileStream, $fp);
                fclose($fp);
            } else {
                copy($file, $tmpName);
            }
            return $tmpName;
        }
        return null;
    }

    /**
     * Create directory
     *
     * @param string
     * @return bool
     */
    public function createDir($dir) {
        return $this->_getFilesystem()->createDir($this->getRelativeDestination($dir));
    }

    /**
     * List contents of a directory
     *
     * @param string
     * @param bool
     * @return array
     */
    public function listContents($dir, $recursive=false) {
        return $this->_getFilesystem()->listContents($this->getRelativeDestination($dir), $recursive);
    }

    /**
     * Clear files and directories in storage
     *
     * @return self
     */
    public function clear() {
        $contents = $this->_getFilesystem()->listContents();
        foreach($contents as $item) {
            if($item['type'] == 'dir') {
                $this->deleteDir($item['filename']);
            } else {
                $this->deleteFile($item['filename']);
            }
        }
        return $this;
    }

    /**
     * Export directories from storage
     *
     * @param  int $offset
     * @param  int $count
     * @return bool|array
     */
    public function exportDirectories($offset = 0, $count = 100) {
        if(!isset($this->_data['dirs'])) {
            $contents = $this->_getFilesystem()->listContents('', true);
            $this->_data['dirs'] = [];
            foreach($contents as $item) {
                if($item['type'] == 'dir') {
                    $this->_data['dirs'][] = [
                        'name' => $item['filename'],
                        'path' => $item['dirname']
                    ];
                }
            }
        }
        return $this->collectData($offset, $count, 'dirs');
    }

    /**
     * Import directories to storage
     *
     * @param  array $dirs
     * @return self
     */
    public function importDirectories($dirs) {
        return $this;
    }

    /**
     * Have we had errors during synchronization
     *
     * @return bool
     */
    public function hasErrors() {
        return !empty($this->_errors);
    }

    /**
     * Export files list in defined range
     *
     * @param  int $offset
     * @param  int $count
     * @return array|bool
     */
    public function exportFiles($offset = 0, $count = 100) {
        if(!isset($this->_data['files'])) {
            $contents = $this->_getFilesystem()->listContents('', true);
            $this->_data['files'] = [];
            foreach($contents as $item) {
                if($item['type'] == 'file') {
                    $this->_data['files'][] = [
                        'filename'      => $item['basename'],
                        'content'       => null,
                        'update_time'   => date('Y-m-d H:i:s', $item['timestamp']),
                        'directory'     => $item['dirname'],
                    ];
                }
            }
        }
        $files = $this->collectData($offset, $count, 'files');
        if(!$files) return false;

        foreach($files as $indx => $file) {
            $files[$indx]['content'] = $this->_getFilesystem()->read($file['directory'].DS.$file['filename']);
        }
        return $files;
    }

    /**
     * Import files list
     *
     * @param  array $files
     * @return self
     */
    public function importFiles($files) {
        if (!is_array($files)) {
            return $this;
        }

        foreach($files as $file) {
            if (!isset($file['filename']) || !strlen($file['filename']) || !isset($file['content'])) {
                continue;
            }

            try {
                if(!$this->_getFilesystem()->has($file['directory'].DS.$file['filename'], $file['content'])) {
                    if($this->getVerbose()) {
                        echo sprintf("Moving file: %s\n", $file['directory'].DS.$file['filename']);
                    }
                    $this->_getFilesystem()->write($file['directory'].DS.$file['filename'], $file['content']);
                }
            } catch (Exception $e) {
                $this->_errors[] = $e->getMessage();
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * Get destination file name checking for existing files
     *
     * @param string
     * @return string
     */
    protected function _getNewDestinationFile($destFile) {
        $fileInfo = pathinfo($destFile);

        if($this->_getFilesystem()->has($destFile)) {
            $index = 1;
            $baseName = $fileInfo['filename'] . '.' . $fileInfo['extension'];
            while($this->fileExists($fileInfo['dirname'] . DIRECTORY_SEPARATOR . $baseName)) {
                $baseName = $fileInfo['filename']. '_' . $index . '.' . $fileInfo['extension'];
                $index ++;
            }
            $destFile = $fileInfo['dirname'] . DIRECTORY_SEPARATOR . $baseName;
        }

        return $destFile;
    }

    /**
     * Collect files and directories from storage
     *
     * @param  int $offset
     * @param  int $count
     * @param  string $type
     * @return array|bool
     */
    public function collectData($offset = 0, $count = 100, $type = 'files') {
        if (!in_array($type, array('files', 'dirs'))) {
            return false;
        }

        $offset = ((int) $offset >= 0) ? (int) $offset : 0;
        $count  = ((int) $count >= 1) ? (int) $count : 1;

        if (is_null($this->_data)) {
            $this->_data = $this->getStorageData();
        }

        $slice = array_slice($this->_data[$type], $offset, $count);
        if (empty($slice)) {
            return false;
        }

        return $slice;
    }

}
