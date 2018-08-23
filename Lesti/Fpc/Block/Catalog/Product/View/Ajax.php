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

    protected $_helperData;

    protected $_helperBlock;

    /**
     *
     * @param \Magento\Framework\Registry $registry
     * @param Lesti\Fpc\Model\Fpc $fpc
     * @param Lesti\Fpc\Helper\Data $helperData
     * @param Lesti\Fpc\Helper\Block $helperBlock
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        Lesti\Fpc\Model\Fpc $fpc,
        Lesti\Fpc\Helper\Data $helperData,
        Lesti\Fpc\Helper\Block $helperBlock
        )
    {
        $this->_registry = $registry;
        $this->_fpc = $fpc;
        $this->_helperData = $helperData;
        $this->_helperBlock = $helperBlock;
    }

    /**
     *
     * @return bool|string
     */
    public function getAjaxUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $id = $this->_getProductId();
        if ($this->_fpc->isActive()
            && in_array('catalog_product_view', $this->_helperData->getCacheableActions())
            && $this->_helperBlock->useRecentlyViewedProducts()
            && $id) {
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
