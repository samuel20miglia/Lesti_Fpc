<?php
declare(strict_types = 1);
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;

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
 * Class Save
 */
class Save  implements ObserverInterface
{
    const PRODUCT_IDS_MASS_ACTION_KEY = 'fpc_product_ids_mass_action';

    protected $fpc;

    protected $_helperData;

    protected $_storeManager;

    protected $_design;

    protected $_catSession;

    /**
     *
     * @param \Lesti\Fpc\Model\Fpc $fpc
     * @param \Lesti\Fpc\Helper\Data $helperData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Magento\Catalog\Model\Session $catSession
     */
    public function __construct(
        \Lesti\Fpc\Model\Fpc $fpc,
        \Lesti\Fpc\Helper\Data $helperData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Catalog\Model\Session $catSession
        )
    {
        $this->fpc = $fpc;
        $this->_helperData = $helperData;
        $this->_storeManager = $storeManager;
        $this->_design = $design;
        $this->_catSession = $catSession;
    }

    /**
     * @param $observer
     */
    public function catalogProductSaveAfter($observer)
    {
        if ($this->fpc->isActive()) {
            $product = $observer->getEvent()->getProduct();
            if ($product->getId()) {
                $this->fpc->clean(sha1('product_' . $product->getId()));

                $origData = $product->getOrigData();
                if (empty($origData)
                    || (!empty($origData) && $product->dataHasChangedFor('status'))
                    ) {
                        $categories = $product->getCategoryIds();
                        foreach ($categories as $categoryId) {
                            $this->fpc->clean(
                                sha1('category_' . $categoryId)
                                );
                        }
                    }
            }
        }
    }

    /**
     * @param $observer
     */
    public function catalogCategorySaveAfter($observer)
    {
        if ($this->fpc->isActive()) {
            $category = $observer->getEvent()->getCategory();
            if ($category->getId()) {
                $this->fpc->clean(sha1('category_' . $category->getId()));
            }
        }
    }

    /**
     * @param $observer
     */
    public function cmsPageSaveAfter($observer)
    {
        if ($this->fpc->isActive()) {
            $page = $observer->getEvent()->getObject();
            if ($page->getId()) {
                $tags = array(sha1('cms_' . $page->getId()),
                    sha1('cms_' . $page->getIdentifier()));
                $this->fpc
                ->clean($tags, Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG);
            }
        }
    }

    /**
     * @param $observer
     */
    public function modelSaveAfter($observer)
    {
        if ($this->fpc->isActive()) {
            $object = $observer->getEvent()->getObject();
            if ($object instanceof \Magento\Cms\Model\Block) {
                $this->_cmsBlockSaveAfter($object);
                //replace Mage_Index_Model_Event with below if ($object instanceof \Magento\)
            } else {
                $dataObject = $object->getDataObject();
                if ($object->getType() === 'mass_action' &&
                    $object->getEntity() === 'catalog_product' &&
                    $dataObject instanceof \Magento\Catalog\Model\Product\Action) {
                        $this->_catalogProductSaveAfterMassAction(
                            $dataObject->getProductIds()
                            );
                    }
            }
        }
    }

    /**
     * @param $observer
     */
    public function reviewDeleteAfter($observer)
    {
        if ($this->fpc->isActive()) {
            $object = $observer->getEvent()->getObject();
            $this->fpc->clean(sha1('product_' . $object->getEntityPkValue()));
        }
    }

    /**
     * @param $observer
     */
    public function reviewSaveAfter($observer)
    {
        if ($this->fpc->isActive()) {
            $object = $observer->getEvent()->getObject();
            $this->fpc->clean(sha1('product_' . $object->getEntityPkValue()));
        }
    }

    /**
     * @param $observer
     */
    public function cataloginventoryStockItemSaveAfter($observer)
    {
        $item = $observer->getEvent()->getItem();
        if ($item->getStockStatusChangedAuto()) {
            $this->fpc->clean(sha1('product_' . $item->getProductId()));
        }
    }

    /**
     * @param Mage_Cms_Model_Block $block
     */
    protected function _cmsBlockSaveAfter(\Magento\Cms\Model\Block $block)
    {
        $this->fpc->clean(sha1('cmsblock_'.$block->getIdentifier()));
    }

    /**
     * @param array $productIds
     */
    protected function _catalogProductSaveAfterMassAction(array $productIds)
    {
        if (!empty($productIds)) {
            $tags = array();
            foreach ($productIds as $productId) {
                $tags[] = sha1('product_' . $productId);
            }
            $this->fpc->clean($tags);
        }
    }
    /**
     * {@inheritDoc}
     * @see \Magento\Framework\Event\ObserverInterface::execute()
     */
    public function execute(Observer $observer) {


    }

}
