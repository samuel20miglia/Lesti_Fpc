<?php
declare(strict_types=1);
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
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_CACHEABLE_ACTIONS = 'system/fpc/cache_actions';
    const XML_PATH_BYPASS_HANDLES = 'system/fpc/bypass_handles';
    const XML_PATH_URI_PARAMS = 'system/fpc/uri_params';
    const XML_PATH_URI_PARAMS_LAYERED_NAVIGATION = 'system/fpc/uri_params_layered_navigation';
    const XML_PATH_CUSTOMER_GROUPS = 'system/fpc/customer_groups';
    const XML_PATH_REFRESH_ACTIONS = 'system/fpc/refresh_actions';
    const XML_PATH_MISS_URI_PARAMS = 'system/fpc/miss_uri_params';
    const LAYOUT_ELEMENT_CLASS = 'Mage_Core_Model_Layout_Element';
    const CACHE_KEY_LAYERED_NAVIGATION_ATTRIBUTES = 'layeredNavigationAttributes';
    const REGISTRY_KEY_PARAMS = 'fpc_params';

    // List of pages that contain layered navigation
    static protected $_pagesWithLayeredNavigation = array(
        'catalogsearch_result_index',
        'catalog_category_layered',
        'catalog_category_view'
    );

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
        \Magento\Framework\App\Cache $cache
    ) {
        $this->_customerSession = $customerSession;
        $this->_registry = $registry;
        $this->request = $request;
        $this->response = $response;
        $this->_storeManager = $storeManager;
        $this->_eventManager = $eventManager;
        $this->_attributeFactory = $attributeFactory;
        $this->cache = $cache;
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getCacheableActions()
    {
        return $this->getCSStoreConfigs(self::XML_PATH_CACHEABLE_ACTIONS);
    }

    /**
     * @return array
     */
    public function getBypassHandles()
    {
        return $this->getCSStoreConfigs(self::XML_PATH_BYPASS_HANDLES);
    }

    /**
     * @return array
     */
    public function getRefreshActions()
    {
        return $this->getCSStoreConfigs(self::XML_PATH_REFRESH_ACTIONS);
    }

    /**
     * @param string $postfix
     * @return string
     */
    public function getKey($postfix = '_page')
    {
        return sha1($this->_getParams()) . $postfix;
    }

    /**
     * @return mixed
     */
    protected function _getParams()
    {
        if (!$this->_registry->registry(self::REGISTRY_KEY_PARAMS)) {
            $request = $this->request->getRequest();

            $params = array('host' => $request->getServer('HTTP_HOST'),
                'port' => $request->getServer('SERVER_PORT'),
                'secure' =>  $this->_storeManager->getStore()->isCurrentlySecure(),
                'full_action_name' => $this->getFullActionName(),
                'ajax' => $request->isAjax(),
            );

            $uriParams = $this->_getUriParams();

            foreach ($request->getParams() as $requestParam =>
                     $requestParamValue) {
                if (!$requestParamValue) {
                    continue;
                }
                foreach ($uriParams as $uriParam) {
                    if ($this->_matchUriParam($uriParam, $requestParam)) {
                        $params['uri_' . $requestParam] = $requestParamValue;
                        break;
                    }
                }
            }
            if ($this->getConfigs(self::XML_PATH_CUSTOMER_GROUPS)) {
                $params['customer_group_id'] = $this->_customerSession->getCustomer()->getGroupId();
            }

            // edit parameters via event
            $parameters = new \Magento\Framework\DataObject();
            $parameters->setValue($params);
            $this->_eventManager->dispatch(
                'fpc_helper_collect_params',
                array('parameters' => $parameters)
            );
            $params = $parameters->getValue();


            $this->_registry->register(self::REGISTRY_KEY_PARAMS, serialize($params));
        }
        return $this->_registry->registry(self::REGISTRY_KEY_PARAMS);
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
     * @return string
     */
    public function getFullActionName()
    {
        $delimiter = '_';
        $request = $this->request->getRequest();
        return $request->getRequestedRouteName() . $delimiter .
        $request->getRequestedControllerName() . $delimiter .
        $request->getRequestedActionName();
    }

    /**
     * @return array
     */
    public function _getUriParams()
    {
        $configParams = $this->getCSStoreConfigs(self::XML_PATH_URI_PARAMS);

        if ($this->getConfigs(self::XML_PATH_URI_PARAMS_LAYERED_NAVIGATION)) {
            $layeredNavigationParams = $this->_getLayeredNavigationAttributes();
        } else {
            $layeredNavigationParams = array();
        }

        return array_merge($configParams, $layeredNavigationParams);
    }

    /**
     * Matches URI param against expression
     * (string comparison or regular expression)
     *
     * @param string $expression
     * @param string $param
     * @return boolean
     */
    protected function _matchUriParam($expression, $param)
    {
        if (substr($expression, 0, 1) === '/' &&
            substr($expression, -1, 1) === '/') {
            return (bool) preg_match($expression, $param);
        } else {
            return $expression === $param;
        }
    }

    /**
     * @param Mage_Core_Controller_Response_Http $response
     * @return string
     */
    public function getContentType()
    {
        foreach ($this->response->getHeaders() as $header) {
            if (isset($header['name']) && $header['name'] === 'Content-Type' && isset($header['value'])) {
                return $header['value'];
            }
        }
        return 'text/html; charset=UTF-8';
    }

    /**
     * @return bool
     */
    public function canCacheRequest()
    {
        $request = $this->request->getRequest();
        if (strtoupper($request->getMethod()) != 'GET') {
            return false;
        }
        $missParams = $this->_getMissUriParams();
        foreach ($missParams as $missParam) {
            $pair = array_map('trim', explode('=', $missParam));
            $key = $pair[0];
            $param = $request->getParam($key);
            if ($param && isset($pair[1]) && preg_match($pair[1], $param)) {
                return false;
            }
        }

        $handles = $this->request->getFullActionName();
        foreach ($this->getBypassHandles() as $handle) {
            if (in_array($handle, $handles)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    protected function _getMissUriParams()
    {
        return $this->getCSStoreConfigs(self::XML_PATH_MISS_URI_PARAMS);
    }

    /**
     * If on a page that contains a layered navigation block, load all attributes that are supposed to show
     *
     * @return array
     */
    protected function _getLayeredNavigationAttributes()
    {
        // List of attributes that are used in layered navigation
        $layeredNavigationAttributes = array();

        $currentFullActionName = $this->getFullActionName();
        if (in_array($currentFullActionName, self::$_pagesWithLayeredNavigation)) {
            /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $attributeCollection */
            $attributeCollection = $this->_attributeFactory->getCollection();

            // The category and search pages may have different filterable attributes, based on how the attributes
            // are configured
            switch ($currentFullActionName) {
                case 'catalogsearch_result_index':
                    $filterableField = 'is_filterable_in_search';
                    break;
                case 'catalog_category_layered':
                case 'catalog_category_view':
                default:
                    $filterableField = 'is_filterable';
            }

            $cacheId = self::CACHE_KEY_LAYERED_NAVIGATION_ATTRIBUTES.'_'.$filterableField;
            $cacheTags = array('FPC', self::CACHE_KEY_LAYERED_NAVIGATION_ATTRIBUTES);
            $layeredNavigationAttributesCache = $this->cache->load($cacheId);

            if (!$layeredNavigationAttributesCache) {
                $attributeCollection->addFieldToFilter($filterableField, array('in' => array(1,2)));
                foreach ($attributeCollection as $attribute) {
                    $layeredNavigationAttributes[] = $attribute->getAttributeCode();
                }
                $this->cache->save(serialize($layeredNavigationAttributes), $cacheId, $cacheTags);
            } else {
                $layeredNavigationAttributes = unserialize($layeredNavigationAttributesCache);
            }
        }

        return $layeredNavigationAttributes;
    }
}