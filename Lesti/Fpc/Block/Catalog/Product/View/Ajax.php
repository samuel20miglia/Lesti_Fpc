<?php
declare(strict_types=1);

namespace Lesti\Fpc\Block\Catalog\Product\View;

use \Magento\Framework\View\Element\Template;
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
 * Class Ajax
 */
class Ajax extends Template
{
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
                    '_secure' => Mage::app()->getStore()->isCurrentlySecure()
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
        $product = Mage::registry('current_product');
        if ($product) {
            return $product->getId();
        }
        return false;
    }
}
