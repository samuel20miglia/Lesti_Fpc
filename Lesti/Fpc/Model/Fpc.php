<?php
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

namespace Lesti\Fpc\Model;

/**
 * Class Lesti_Fpc_Model_Fpc
 */
class Fpc extends \Magento\Framework\App\Cache
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
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Lesti\Fpc\Model\Fpc\CacheItemFactory
     */
    protected $fpcFpcCacheItemFactory;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Lesti\Fpc\Model\Fpc\CacheItemFactory $fpcFpcCacheItemFactory,
        \Magento\Framework\App\Cache\Frontend\Pool $pool
    )
    {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->fpcFpcCacheItemFactory = $fpcFpcCacheItemFactory;
        $this->eventManager = $eventManager;
        $this->scopeConfig = $scopeConfig;
        /*
         * If the version of Zend Framework is older than 1.12, fallback to the
         * legacy cache settings.
         * See http://framework.zend.com/issues/browse/ZF-12047
         */
        if (\Zend_Version::compareVersion('1.12.0') > 0) {
            $this->_defaultBackendOptions = $this->_legacyDefaultBackendOptions;
        }
//         $node = $this->scopeConfig->getValue('global/fpc');
//         $options = array();
//         if ($node) {
//             $options = $node->asArray();
//         }
           parent::__construct($pool);
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
    public function save($item, $id, $tags=[], $lifeTime=null)
    {
        if (!in_array(self::CACHE_TAG, $tags)) {
            $tags[] = self::CACHE_TAG;
        }
        if (is_null($lifeTime)) {
            $lifeTime = (int) $this->scopeConfig->getValue('lifetime');
        }

        $data = [
            array($item),
            $item,
            $item
        ];
        // edit cached object
        $cacheData = $this->dataObjectFactory->create();
        $cacheData->setCachedata($data);
        $cacheData->setCacheId($id);
        $cacheData->setTags($tags);
        $cacheData->setLifeTime($lifeTime);
        $this->eventManager->dispatch(
            'fpc_save_data_before',
            ['cache_data' => $cacheData]
        );
        $data = $cacheData->getCachedata();
        $id = $cacheData->getCacheId();
        $tags = $cacheData->getTags();
        $lifeTime = $cacheData->getLifeTime();

        $compressLevel = $this->scopeConfig->getValue(self::GZCOMPRESS_LEVEL_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $data = serialize($data);
        if ($compressLevel != -2) {
            $data = gzcompress($data, $compressLevel);
        }

        return $this->getFrontend()->save(
            $data,
            $id,
            $tags,
            $lifeTime
        );
    }

    /**
     * @param string $id
     * @return boolean|\Lesti_Fpc_Model_Fpc_CacheItem
     */
    public function load($id)
    {
        $data = parent::load($id);
        if ($data === false) {
            return false;
        }
        $compressLevel = $this->scopeConfig->getValue(self::GZCOMPRESS_LEVEL_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($data !== false && $compressLevel != -2) {
            $data = gzuncompress($data);
        }

        $data = unserialize($data);

        return $this->fpcFpcCacheItemFactory->create($data, $data[1], $data[2]);
    }

    /**
     * Clean cached data by specific tag
     *
     * @param   array $tags
     * @return  bool
     */
    public function clean($tags=array())
    {
        $mode = \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG;
        if ($tags) {
            if (!is_array($tags)) {
                $tags = array($tags);
            }
            $result = $this->_frontend->clean($mode, $tags);
        } else {
            /** @var $cacheFrontend \Magento\Framework\Cache\FrontendInterface */
            foreach ($this->_frontendPool as $cacheFrontend) {
                if ($cacheFrontend->clean()) {
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return true;
    }



}
