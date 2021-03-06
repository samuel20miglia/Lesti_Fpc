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

namespace Lesti\Fpc\Helper;

use Magento\Customer\Model\Session;

/**
 * Class Lesti_Fpc_Helper_Block
 */
class Block extends \Lesti\Fpc\Helper\AbstractData
{
    const DYNAMIC_BLOCKS_XML_PATH = 'system/fpc/dynamic_blocks';
    const LAZY_BLOCKS_XML_PATH = 'system/fpc/lazy_blocks';
    const LAZY_BLOCKS_VALID_SESSION_PARAM = 'fpc_lazy_blocks_valid';
    const USE_RECENTLY_VIEWED_PRODUCTS_XML_PATH =
        'system/fpc/use_recently_viewed_products';

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $session;

    protected $design;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Session $session,
        \Magento\Framework\View\DesignInterface $design
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->design = $design;
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
        $session = $this->session;
        $sessionHash = $session->getData(self::LAZY_BLOCKS_VALID_SESSION_PARAM);
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
        $request = $this->request;
        $params['host'] = $request->getServer('HTTP_HOST');
        $params['port'] = $request->getServer('SERVER_PORT');
        // store
        $storeCode = $this->storeManager->getStore()->getCode();
        if ($storeCode) {
            $params['store'] = $storeCode;
        }
        // currency
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        if ($currencyCode) {
            $params['currency'] = $currencyCode;
        }
        $customerSession = $this->session;
        $params['customer_group_id'] = $customerSession->getCustomerGroupId();
        $design = $this->design;
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
        return (bool)$this->scopeConfig->getValue(
            self::USE_RECENTLY_VIEWED_PRODUCTS_XML_PATH
        , \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
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
        is_subclass_of($block, 'Mage_Cms_Block_Block')) {
            $cacheTags[] = sha1('cmsblock');
            $cacheTags[] = sha1('cmsblock_' . $block->getBlockId());
        }
        return $cacheTags;
    }

}
