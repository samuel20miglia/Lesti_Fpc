<?php
declare(strict_types=1);

namespace Lesti\Fpc\Model;
use Lesti\Fpc\Object\CacheItem;

/**
 * Lesti_Fpc (http:gordonlesti.com/lestifpc)
 *
 * PHP version 5
 *
 * @link      https://github.com/GordonLesti/Lesti_Fpc
 * @package   Lesti_Fpc
 * @author    Gordon Lesti <info@gordonlesti.com>
 * @copyright Copyright (c) 2013-2016 Gordon Lesti (http://gordonlesti.com)
 * @license   http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

/**
 * Class Fpc
 *//**
 * @param \Magento\Framework\App\Helper\Context $context
 * @param \Magento\Customer\Model\Session $customerSession
 * @param CustomerViewHelper $customerViewHelper
 */
class Fpc
{
    const GZCOMPRESS_LEVEL_XML_PATH = 'system/fpc/gzcompress_level';
    const CACHE_TAG = 'FPC';

    /**
     * Default options for default backend
     *
     * @var array
     */
    protected $_defaultBackendOptions = array(
        'hashed_directory_level'    => 6,
        'hashed_directory_perm'    => 0777,
        'file_name_prefix'          => 'fpc',
    );

    /**
     * Default options for default backend used by Zend Framework versions
     * older than 1.12.0
     *
     * @var array
     */
    protected $_legacyDefaultBackendOptions = array(
        'hashed_directory_level'    => 6,
        'hashed_directory_umask'    => 0777,
        'file_name_prefix'          => 'fpc',
    );

    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    protected $_cacheTypeList;

    protected $_cacheFrontendPool;


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_eventManager = $eventManager;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_cacheTypeList = $cacheTypeList;
        /*
         * If the version of Zend Framework is older than 1.12, fallback to the
         * legacy cache settings.
         * See http://framework.zend.com/issues/browse/ZF-12047
         */
        if (Zend_Version::compareVersion('1.12.0') > 0) {
            $this->_defaultBackendOptions = $this->_legacyDefaultBackendOptions;
        }
        $node = $this->_scopeConfig->getNode('global/fpc');
        $options = array();
        if ($node) {
            $options = $node->asArray();
        }
        parent::__construct($options);
    }

    /**
     * Save data
     *
     * @param mixed $item
     * @param string $id
     * @param array $tags
     * @param int $lifeTime
     * @return bool
     */
    public function save($item, $id, $tags=array(), $lifeTime=null)
    {
        if (!in_array(self::CACHE_TAG, $tags)) {
            $tags[] = self::CACHE_TAG;
        }
        if (is_null($lifeTime)) {
            $lifeTime = (int) $this->getFrontend()->getOption('lifetime');
        }
        $data = array(
            $item->getContent(),
            $item->getTime(),
            $item->getContentType(),
        );
        // edit cached object
        $cacheData = new \Magento\Framework\DataObject();
        $cacheData->setCachedata($data);
        $cacheData->setCacheId($id);
        $cacheData->setTags($tags);
        $cacheData->setLifeTime($lifeTime);
        $this->_eventManager->dispatchEvent(
            'fpc_save_data_before',
            ['cache_data' => $cacheData]
        );
        $data = $cacheData->getCachedata();
        $id = $cacheData->getCacheId();
        $tags = $cacheData->getTags();
        $lifeTime = $cacheData->getLifeTime();

        $compressLevel = $this->_scopeConfig->getStoreConfig(self::GZCOMPRESS_LEVEL_XML_PATH);
        $data = serialize($data);
        if ($compressLevel != -2) {
            $data = gzcompress($data, $compressLevel);
        }

        return $this->_frontend->save(
            $data,
            $this->_id($id),
            $this->_tags($tags),
            $lifeTime
        );
    }

    /**
     * @param string $id
     * @return boolean|\CacheItem
     */
    public function load($id)
    {
        $data = parent::load($id);
        if ($data === false) {
            return false;
        }
        $compressLevel = $this->_scopeConfig->getStoreConfig(self::GZCOMPRESS_LEVEL_XML_PATH);
        if ($data !== false && $compressLevel != -2) {
            $data = gzuncompress($data);
        }

        $data = unserialize($data);

        return new CacheItem($data[0], $data[1], $data[2]);
    }

    /**
     * Clean cached data by specific tag
     *
     * @param   array $tags
     * @return  bool
     */
    public function clean($tags=array())
    {
        $mode = Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG;
        if (!empty($tags)) {
            if (!is_array($tags)) {
                $tags = array($tags);
            }
            $res = $this->_frontend->clean($mode, $this->_tags($tags));
        } else {
            $res = $this->_frontend->clean($mode, [self::CACHE_TAG]);
            $res = $res &&
                $this->_frontend->clean(
                    $mode,
                    array(Mage_Core_Model_Config::CACHE_TAG)
                );
        }
        return $res;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->useCache('fpc');
    }

}
