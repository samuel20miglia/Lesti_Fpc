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

    protected $_fpc;

    protected $_helperData;

    protected $_helperBlock;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Lesti\Fpc\Model\Fpc $fpc,
        \Lesti\Fpc\Helper\Data $helperData,
        \Lesti\Fpc\Helper\Block $helperBlock,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->_fpc = $fpc;
        $this->_helperData = $helperData;
        $this->_helperBlock = $helperBlock;
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
        if ($this->_fpc->isActive() &&
            in_array(
                'catalog_product_view',
                $this->_helperData->getCacheableActions()
            ) &&
            $this->_helperBlock->useRecentlyViewedProducts() &&
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
