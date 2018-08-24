<?php
declare(strict_types = 1);
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;

/**
 * Lesti_Fpc (http:gordonlesti.com/lestifpc)
 *
 *
 * @link      https://github.com/GordonLesti/Lesti_Fpc
 * @package   Lesti_Fpc
 * @author    Gordon Lesti <info@gordonlesti.com>
 * @copyright Copyright (c) 2013-2016 Gordon Lesti (http://gordonlesti.com)
 * @license   http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

/**
 * Class Tags
 */
class Tags  implements ObserverInterface
{

    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    protected $fpc;

    protected $_helperData;

    protected $_storeManager;

    protected $_design;

    protected $_catSession;

    protected $_productCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Lesti\Fpc\Model\Fpc $fpc,
        \Lesti\Fpc\Helper\Data $helperData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Catalog\Model\Session $catSession,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
        )
    {
        $this->fpc = $fpc;
        $this->_helperData = $helperData;
        $this->_storeManager = $storeManager;
        $this->_design = $design;
        $this->_catSession = $catSession;
        $this->_scopeConfig = $scopeConfig;
        $this->_productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param $observer
     */
    public function fpcObserverCollectCacheTags($observer)
    {
        /** @var Lesti_Fpc_Helper_Data $helper */
        $helper = $this->_helperData;
        $fullActionName = $helper->getFullActionName();
        $cacheTags = [];
        $request = $this->getRequest();
        switch ($fullActionName) {
            case 'cms_index_index' :
                $cacheTags = $this->getCmsIndexIndexCacheTags();
                break;
            case 'cms_page_view' :
                $cacheTags = $this->getCmsPageViewCacheTags($request);
                break;
            case 'catalog_product_view' :
                $cacheTags = $this->getCatalogProductViewCacheTags($request);
                break;
            case 'catalog_category_view' :
                $cacheTags = $this->getCatalogCategoryViewCacheTags($request);
                break;
        }

        $cacheTagObject = $observer->getEvent()->getCacheTags();
        $additionalCacheTags = $cacheTagObject->getValue();
        $additionalCacheTags = array_merge($additionalCacheTags, $cacheTags);
        $cacheTagObject->setValue($additionalCacheTags);
    }

    /**
     * @return array
     */
    protected function getCmsIndexIndexCacheTags()
    {
        $cacheTags = [];
        $cacheTags[] = sha1('cms');
        $pageId = $this->_storeManager->getStoreConfig(
            \Magento\Cms\Helper\Page::XML_PATH_HOME_PAGE
            );
        if ($pageId) {
            $cacheTags[] = sha1('cms_' . $pageId);
        }
        return $cacheTags;
    }

    /**
     * @param Mage_Core_Controller_Request_Http $request
     * @return array
     */
    protected function getCmsPageViewCacheTags(
        \Magento\Framework\App\Request\Http $request
        )
    {
        $cacheTags = array();
        $cacheTags[] = sha1('cms');
        $pageId = $request->getParam(
            'page_id',
            $request->getParam('id', false)
            );
        if ($pageId) {
            $cacheTags[] = sha1('cms_' . $pageId);
        }
        return $cacheTags;
    }

    /**
     * @param Mage_Core_Controller_Request_Http $request
     * @return array
     */
    protected function getCatalogProductViewCacheTags(
        \Magento\Framework\App\Request\Http $request
        )
    {
        $cacheTags = array();
        $cacheTags[] = sha1('product');
        $productId = (int)$request->getParam('id');
        if ($productId) {
            $cacheTags[] = sha1('product_' . $productId);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            // configurable product
            $configurableProduct = $objectManager->get('Magento\Catalog\Model\ResourceModel\Product\Collection');
            // get all childs of this product and add the cache tag
            $childIds = $configurableProduct->getChildrenIds($productId);
            foreach ($childIds as $childIdGroup) {
                foreach ($childIdGroup as $childId) {
                    $cacheTags[] = sha1('product_' . $childId);
                }
            }
            // get all parents of this product and add the cache tag
            $parentIds = $configurableProduct
            ->getParentIdsByChild($productId);
            foreach ($parentIds as $parentId) {
                $cacheTags[] = sha1('product_' . $parentId);
            }

            // grouped product
            $groupedProduct = $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', ['eq' => 'grouped']);
            // get all childs of this product and add the cache tag
            $childIds = $groupedProduct->getChildrenIds($productId);
            foreach ($childIds as $childIdGroup) {
                foreach ($childIdGroup as $childId) {
                    $cacheTags[] = sha1('product_' . $childId);
                }
            }
            // get all parents of this product and add the cache tag
            $parentIds = $groupedProduct->getParentIdsByChild($productId);
            foreach ($parentIds as $parentId) {
                $cacheTags[] = sha1('product_' . $parentId);
            }

            $categoryId = (int)$request->getParam('category', false);
            if ($categoryId) {
                $cacheTags[] = sha1('category');
                $cacheTags[] = sha1('category_' . $categoryId);
            }
        }
        return $cacheTags;
    }

    /**
     * @param Mage_Core_Controller_Request_Http $request
     * @return array
     */
    protected function getCatalogCategoryViewCacheTags(
        \Magento\Framework\App\Request\Http $request
        )
    {
        $cacheTags = [];
        $cacheTags[] = sha1('category');
        $categoryId = (int)$request->getParam('id', false);
        if ($categoryId) {
            $cacheTags[] = sha1('category_' . $categoryId);
        }
        return $cacheTags;
    }
    /**
     * {@inheritDoc}
     * @see \Magento\Framework\Event\ObserverInterface::execute()
     */
    public function execute(Observer $observer) {
        // TODO: Auto-generated method stub

    }

}
