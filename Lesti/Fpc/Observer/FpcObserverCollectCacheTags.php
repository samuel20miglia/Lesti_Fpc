<?php
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author vegans
 *
 */
class FpcObserverCollectCacheTags implements ObserverInterface
{

    /**
     *
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $_helperData;

    /**
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     *
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Lesti\Fpc\Helper\Data $helperData
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Lesti\Fpc\Helper\Data $helperData,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory)
    {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->_helperData = $helperData;
        $this->_productCollectionFactory = $productCollectionFactory;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Magento\Framework\Event\ObserverInterface::execute()
     */
    public function execute(Observer $observer)
    {
        /** @var Lesti_Fpc_Helper_Data $helper */
        $helper = $this->_helperData;
        $fullActionName = $helper->getFullActionName();
        $cacheTags = array();
        $request = $this->request;
        switch ($fullActionName) {
            case 'cms_index_index':
                $cacheTags = $this->getCmsIndexIndexCacheTags();
                break;
            case 'cms_page_view':
                $cacheTags = $this->getCmsPageViewCacheTags($request);
                break;
            case 'catalog_product_view':
                $cacheTags = $this->getCatalogProductViewCacheTags($request);
                break;
            case 'catalog_category_view':
                $cacheTags = $this->getCatalogCategoryViewCacheTags($request);
                break;
        }

        $cacheTagObject = $observer->getEvent()->getCacheTags();
        $additionalCacheTags = $cacheTagObject->getValue();
        $additionalCacheTags = array_merge($additionalCacheTags, $cacheTags);
        $cacheTagObject->setValue($additionalCacheTags);
    }

    /**
     *
     * @return array
     */
    protected function getCmsIndexIndexCacheTags()
    {
        $cacheTags = array();
        $cacheTags[] = sha1('cms');
        $pageId = $this->scopeConfig->getValue(\Magento\Cms\Helper\Page::XML_PATH_HOME_PAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($pageId) {
            $cacheTags[] = sha1('cms_' . $pageId);
        }
        return $cacheTags;
    }

    /**
     *
     * @param \Magento\Framework\App\Request\Http $request
     * @return array
     */
    protected function getCmsPageViewCacheTags(\Magento\Framework\App\Request\Http $request)
    {
        $cacheTags = array();
        $cacheTags[] = sha1('cms');
        $pageId = $request->getParam('page_id', $request->getParam('id', false));
        if ($pageId) {
            $cacheTags[] = sha1('cms_' . $pageId);
        }
        return $cacheTags;
    }

    /**
     *
     * @param \Magento\Framework\App\Request\Http $request
     * @return array
     */
    protected function getCatalogProductViewCacheTags(\Magento\Framework\App\Request\Http $request)
    {
        $cacheTags = array();
        $cacheTags[] = sha1('product');
        $productId = (int) $request->getParam('id');
        if ($productId) {
            $cacheTags[] = sha1('product_' . $productId);

            // configurable product
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
            $parentIds = $configurableProduct->getParentIdsByChild($productId);
            foreach ($parentIds as $parentId) {
                $cacheTags[] = sha1('product_' . $parentId);
            }

            // grouped product
            // grouped product
            $groupedProduct = $this->productCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id', [
                'eq' => 'grouped'
            ]);
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

            $categoryId = (int) $request->getParam('category', false);
            if ($categoryId) {
                $cacheTags[] = sha1('category');
                $cacheTags[] = sha1('category_' . $categoryId);
            }
        }
        return $cacheTags;
    }

    /**
     *
     * @param \Magento\Framework\App\Request\Http $request
     * @return array
     */
    protected function getCatalogCategoryViewCacheTags(\Magento\Framework\App\Request\Http $request)
    {
        $cacheTags = array();
        $cacheTags[] = sha1('category');
        $categoryId = (int) $request->getParam('id', false);
        if ($categoryId) {
            $cacheTags[] = sha1('category_' . $categoryId);
        }
        return $cacheTags;
    }
}

