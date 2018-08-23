<?php
declare(strict_types = 1);
namespace Lesti\Fpc\Block\Catalog\Product\View;

use \Magento\Framework\View\Element\Template;

/**
 * Lesti_Fpc (http:gordonlesti.com/lestifpc)
 *
 * PHP version 5
 *
 * @link https://github.com/GordonLesti/Lesti_Fpc
 * @package Lesti_Fpc
 * @author Gordon Lesti <info@gordonlesti.com>
 * @copyright Copyright (c) 2013-2016 Gordon Lesti (http://gordonlesti.com)
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

/**
 * Class Ajax
 */
class Ajax extends Template
{

    protected $_registry;

    protected $_fpc;

    /**
     * ...
     * ...
     *
     * @param \Magento\Framework\Registry $registry,
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        Lesti\Fpc\Model\Fpc $fpc
        )
    {
        $this->_registry = $registry;
        $this->_fpc = $fpc;
    }

    /**
     *
     * @return bool|string
     */
    public function getAjaxUrl()
    {
        $id = $this->_getProductId();
        if ($this->_fpc->isActive()
            && in_array('catalog_product_view', Mage::helper('fpc')->getCacheableActions())
            && Mage::helper('fpc/block')->useRecentlyViewedProducts()
            && $id) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            return $this->getUrl('fpc/catalog_product/view', array(
                'id' => $id,
                '_secure' => $objectManager->getStore()
                    ->isCurrentlySecure()
            ));
        }
        return false;
    }

    /**
     *
     * @return bool
     */
    protected function _getProductId()
    {
        $product = $this->_registry('current_product');
        if ($product) {
            return $product->getId();
        }
        return false;
    }
}
