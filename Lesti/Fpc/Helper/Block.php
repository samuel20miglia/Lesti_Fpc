<?php
/**
 * Copyright © Lesti, All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lesti\Fpc\Helper;

use Magento\Contact\Model\ConfigInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Contact base helper
 *
 * @deprecated 100.2.0
 * @see \Magento\Contact\Model\ConfigInterface
 */
class Block extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DYNAMIC_BLOCKS_XML_PATH = 'system/fpc/dynamic_blocks';
    const LAZY_BLOCKS_XML_PATH = 'system/fpc/lazy_blocks';
    const LAZY_BLOCKS_VALID_SESSION_PARAM = 'fpc_lazy_blocks_valid';
    const USE_RECENTLY_VIEWED_PRODUCTS_XML_PATH =
        'system/fpc/use_recently_viewed_products';

    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var array
     */
    private $postData = null;

    protected $_registry;

    protected $request;

    protected $response;

    protected $_storeManager;

    /**
   * @var EventManager
   */
    protected $_eventManager;

    protected $_attributeFactory;

    protected $cache;

    protected $_themeProvider;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerViewHelper $customerViewHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory,
        \Magento\Framework\App\Cache $cache,
        \Magento\Framework\View\Design\Theme\ThemeProviderInterface $themeProvider
    ) {
        $this->_customerSession = $customerSession;
        $this->_registry = $registry;
        $this->request = $request;
        $this->response = $response;
        $this->_storeManager = $storeManager;
        $this->_eventManager = $eventManager;
        $this->_attributeFactory = $attributeFactory;
        $this->cache = $cache;
        $this->_themeProvider = $themeProvider;
        parent::__construct($context);
    }

    public function getCSStoreConfigs($path)
    {
        $configs = $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($configs) {
            return array_unique(array_map('trim', explode(',', $configs)));
        }

        return array();
    }

    public function getConfigs($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return array
     */
    public function getDynamicBlocks()
    {
        return $this->getCSStoreConfigs(self::DYNAMIC_BLOCKS_XML_PATH);
    }

    /**
     * @return array
     */
    public function getLazyBlocks()
    {
        return $this->getCSStoreConfigs(self::LAZY_BLOCKS_XML_PATH);
    }

    /**
     * @return bool
     */
    public function areLazyBlocksValid()
    {
        $hash = $this->_getLazyBlocksValidHash();
        $sessionHash = $this->_customerSession->getData(self::LAZY_BLOCKS_VALID_SESSION_PARAM);
        if ($sessionHash === false || $hash != $sessionHash) {
            $session->setData(self::LAZY_BLOCKS_VALID_SESSION_PARAM, $hash);
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    protected function _getLazyBlocksValidHash()
    {
        $params = array();
        $request = $this->request->getRequest();
        $params['host'] = $request->getServer('HTTP_HOST');
        $params['port'] = $request->getServer('SERVER_PORT');
        // store
        $storeCode = $this->_storeManager->getStore()->getCode();
        if ($storeCode) {
            $params['store'] = $storeCode;
        }
        // currency
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        if ($currencyCode) {
            $params['currency'] = $currencyCode;
        }
        $params['customer_group_id'] = $this->_customerSession->getCustomer()->getGroupId();


        $themeId = $this->_scopeConfig->getValue(
            \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId()
        );

        $design = $this->_themeProvider->getThemeById($themeId);
        
        $params['design'] = $design->getPackageName().'_'.
            $design->getTheme('template');

        $params['blocks'] = implode(',', $this->getLazyBlocks());

        return sha1(serialize($params));
    }

    /**
     * @param $blockName
     * @return string
     */
    public function getPlaceholderHtml($blockName)
    {
        return '<!-- fpc ' . sha1($blockName) . ' -->';
    }

    /**
     * @param $blockName
     * @return string
     */
    public function getKey($blockName)
    {
        return sha1($blockName) . '_block';
    }

    /**
     * @return mixed
     */
    public function useRecentlyViewedProducts()
    {
        return (bool)$this->getConfigs(
            self::USE_RECENTLY_VIEWED_PRODUCTS_XML_PATH
        );
    }

    /**
     * @param $block
     * @return array
     */
    public function getCacheTags($block)
    {
        $cacheTags = array();
        $blockName = $block->getNameInLayout();
        if ($blockName == 'product_list') {
            $cacheTags[] = sha1('product');
            foreach ($block->getLoadedProductCollection() as $product) {
                $cacheTags[] = sha1('product_' . $product->getId());
            }
        } else if ($block instanceof \Magento\Cms\Block\Block ||
        is_subclass_of($block, \Magento\Cms\Block\Block::class)) {
            $cacheTags[] = sha1('cmsblock');
            $cacheTags[] = sha1('cmsblock_' . $block->getBlockId());
        }
        return $cacheTags;
    }
}