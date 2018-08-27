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

namespace Lesti\Fpc\Block\Catalog\Product\View;

/**
 * Class Lesti_Fpc_Block_Catalog_Product_View_Ajax
 */
class Ajax extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @return bool|string
     */
    public function getAjaxUrl()
    {
        $id = $this->_getProductId();
        if (Mage::getSingleton('fpc/fpc')->isActive() &&
            in_array(
                'catalog_product_view',
                Mage::helper('fpc')->getCacheableActions()
            ) &&
            Mage::helper('fpc/block')->useRecentlyViewedProducts() &&
            $id
        ) {
            return $this->getUrl(
                'fpc/catalog_product/view',
                array(
                    'id' => $id,
                    '_secure' => $this->storeManager->getStore()->isCurrentlySecure()
                )
            );
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function _getProductId()
    {
        $product = $this->registry->registry('current_product');
        if ($product) {
            return $product->getId();
        }
        return false;
    }
}
