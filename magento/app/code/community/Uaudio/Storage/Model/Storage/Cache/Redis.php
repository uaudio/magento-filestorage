<?php
require_once('Uaudio/vendor/autoload.php');
use League\Flysystem\Cached\Storage\Predis as Cache;

/**
 * Flysystem redis cache using magento cache settings
 *
 * @category    Uaudio
 * @package     Uaudio_Storage
 * @author      Universal Audio <web-dev@uaudio.com>
 */
class Uaudio_Storage_Model_Storage_Cache_Redis extends Cache {

    /**
     * Initialize redis cache object using magento cache settings
     */
    public function __construct() {
        $options = Mage::getConfig()->getNode('global/cache');
        $options = $options ? $options->asArray() : [];
        $options = $options['backend_options'];
        $options['prefix'] = Cm_Cache_Backend_Redis::PREFIX_KEY;

        $client = new \Predis\Client([
            'scheme' => 'tcp',
            'host'   => $options['server'],
            'port'   => $options['port'],
            'database' => $options['database'],
        ], ['prefix' => $options['prefix']]);
        parent::__construct($client);
    }
}
