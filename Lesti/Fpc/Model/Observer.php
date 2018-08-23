<?php
declare(strict_types=1);

namespace Lesti\Fpc\Model;

use Lesti\Fpc\Object\CacheItem;
use Lesti\Fpc\Helper\Block;

/**
 *
 * @author samuel vegans
 *
 */
class Observer
{

    const CUSTOMER_SESSION_REGISTRY_KEY = 'fpc_customer_session';
    const SHOW_AGE_XML_PATH = 'system/fpc/show_age';
    const FORM_KEY_PLACEHOLDER = '<!-- fpc form_key_placeholder -->';
    const SESSION_ID_PLACEHOLDER = '<!-- fpc session_id_placeholder -->';

    protected $_cached = false;
    protected $_html = [];
    protected $_placeholder = [];
    protected $_cacheTags = [];

    protected $_cache;
    protected $_customerSession;
    protected $_helperData;
    protected $_helperBlock;
    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;


    protected $_coreSession;

    protected $_helperMessages;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory,
        \Magento\Framework\App\Cache $cache,
        \Lesti\Fpc\Helper\Data $helperData,
        \Lesti\Fpc\Helper\Block $helperBlock,
        \Lesti\Fpc\Helper\Block\Messages $helperMessages,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
     )
    {
        $this->_cache = $cache;
        $this->_helperData = $helperData;
        $this->_helperBlock = $helperBlock;
        $this->_customerSession = $customerSession;
        $this->_scopeConfig = $scopeConfig;
        $this->_eventManager = $eventManager;
        $this->_coreSession = $coreSession;
        $this->_helperMessages = $helperMessages;
    }

    /**
     * @param $observer
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function controllerActionLayoutGenerateBlocksBefore($observer)
    {
        if ($this->_getFpc()->isActive() &&
            !$this->_cached &&
            $this->_helperData->canCacheRequest()) {
                $key = $this->_helperData->getKey();
                if ($object = $this->_getFpc()->load($key)) {
                    $time = $object->getTime();
                    $body = $object->getContent();
                    $this->_cached = true;
                    $session = $this->_customerSession;
                    $lazyBlocks = $this->_helperBlock->getLazyBlocks();
                    $dynamicBlocks = $this->_helperBlock->getDynamicBlocks();
                    $blockHelper = $this->_helperBlock;
                    if ($blockHelper->areLazyBlocksValid()) {
                        foreach ($lazyBlocks as $blockName) {
                            $this->_placeholder[] = $blockHelper
                            ->getPlaceholderHtml($blockName);
                            $this->_html[] = $session
                            ->getData('fpc_lazy_block_' . $blockName);
                        }
                    } else {
                        $dynamicBlocks = array_merge($dynamicBlocks, $lazyBlocks);
                    }
                    // prepare Layout
                    $layout = $this->_prepareLayout(
                        $observer->getEvent()->getLayout(),
                        $dynamicBlocks
                        );
                    // insert dynamic blocks
                    $this->_insertDynamicBlocks(
                        $layout,
                        $session,
                        $dynamicBlocks,
                        $lazyBlocks
                        );
                    $this->_placeholder[] = self::SESSION_ID_PLACEHOLDER;
                    $this->_html[] = $session->getEncryptedSessionId();
                    $this->_replaceFormKey();
                    $body = str_replace($this->_placeholder, $this->_html, $body);

                    if ($this->getConfigs(self::SHOW_AGE_XML_PATH)) {
                        $this->getResponse()->setHeader('Age', time() - $time);
                    }
                    $this->getResponse()->setHeader('Content-Type', $object->getContentType(), true);
                    $response = $this->getResponse();
                    $response->setBody($body);
                    $this->_eventManager->dispatch(
                        'fpc_http_response_send_before',
                        ['response' => $response]
                        );
                    $response->sendResponse();
                    exit;
                }
                if ($this->getConfigs(self::SHOW_AGE_XML_PATH)) {
                    $this->getResponse()->setHeader('Age', 0);
                }
            }
    }

    /**
     * @param $observer
     */
    public function httpResponseSendBefore($observer)
    {
        $response = $observer->getEvent()->getResponse();
        if ($this->_getFpc()->isActive() &&
            !$this->_cached &&
            $this->_helperData->canCacheRequest() &&
            $response->getHttpResponseCode() == 200) {
                $fullActionName = $this->_helperData->getFullActionName();
                $cacheableActions = $this->_helperData->getCacheableActions();
                if (in_array($fullActionName, $cacheableActions)) {
                    $key = $this->_helperData->getKey();
                    $body = $observer->getEvent()->getResponse()->getBody();
                    $session = $this->_coreSession;
                    $formKey = $session->getFormKey();
                    if ($formKey) {
                        $body = str_replace(
                            $formKey,
                            self::FORM_KEY_PLACEHOLDER,
                            $body
                            );
                        $this->_placeholder[] = self::FORM_KEY_PLACEHOLDER;
                        $this->_html[] = $formKey;
                    }
                    $sid = $session->getEncryptedSessionId();
                    if ($sid) {
                        $body = str_replace(
                            $sid,
                            self::SESSION_ID_PLACEHOLDER,
                            $body
                            );
                        $this->_placeholder[] = self::SESSION_ID_PLACEHOLDER;
                        $this->_html[] = $sid;
                    }
                    // edit cacheTags via event
                    $cacheTags = new \Magento\Framework\DataObject();
                    $cacheTags->setValue($this->_cacheTags);
                    $this->_eventManager->dispatch(
                        'fpc_observer_collect_cache_tags',
                        array('cache_tags' => $cacheTags)
                        );
                    $this->_cacheTags = $cacheTags->getValue();
                    $this->_getFpc()->save(
                        new CacheItem($body, time(), $this->_helperData->getContentType($response)),
                        $key,
                        $this->_cacheTags
                        );
                    $this->_cached = true;
                    $body = str_replace($this->_placeholder, $this->_html, $body);
                    $observer->getEvent()->getResponse()->setBody($body);
                }
            }
    }

    /**
     * @param $observer
     */
    public function coreBlockAbstractToHtmlAfter($observer)
    {
        if ($this->_getFpc()->isActive() &&
            !$this->_cached &&
            $this->_helperData->canCacheRequest()) {
                $fullActionName = $this->_helperData->getFullActionName();
                $block = $observer->getEvent()->getBlock();
                $blockName = $block->getNameInLayout();
                $dynamicBlocks = $this->_helperBlock->getDynamicBlocks();
                $lazyBlocks = $this->_helperBlock->getLazyBlocks();
                $dynamicBlocks = array_merge($dynamicBlocks, $lazyBlocks);
                $cacheableActions = $this->_helperData->getCacheableActions();
                if (in_array($fullActionName, $cacheableActions)) {
                    $this->_cacheTags = array_merge(
                        $this->_helperBlock->getCacheTags($block),
                        $this->_cacheTags
                        );
                    if (in_array($blockName, $dynamicBlocks)) {
                        $placeholder = $this->_helperBlock
                        ->getPlaceholderHtml($blockName);
                        $html = $observer->getTransport()->getHtml();
                        $this->_html[] = $html;
                        $this->_placeholder[] = $placeholder;
                        $observer->getTransport()->setHtml($placeholder);
                    }
                }
            }
    }

    public function controllerActionPostdispatch()
    {
        if ($this->_getFpc()->isActive()) {
            $fullActionName = $this->_helperData->getFullActionName();
            if (in_array(
                $fullActionName,
                $this->_helperData->getRefreshActions()
                )) {
                    $session = $this->_customerSession;
                    $session->setData(
                        Block::LAZY_BLOCKS_VALID_SESSION_PARAM,
                        false
                        );
                }
        }
    }

    /**
     * @return Lesti_Fpc_Model_Fpc
     */
    protected function _getFpc()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        return $objectManager->get('Lesti\Fpc\Model\Fpc');
    }

    /**
     * @param Mage_Core_Model_Layout $layout
     * @param array $dynamicBlocks
     * @return Mage_Core_Model_Layout
     */
    protected function _prepareLayout(
        \Magento\Framework\View\Layout $layout,
        array $dynamicBlocks
        )
    {
        $xml = $layout->getNode();
        $cleanXml = simplexml_load_string(
            '<layout/>',
            $this->_helperData::LAYOUT_ELEMENT_CLASS
            );
        $types = array('block', 'reference', 'action');
        foreach ($dynamicBlocks as $blockName) {
            foreach ($types as $type) {
                $xPath = $xml->xpath(
                    "//" . $type . "[@name='" . $blockName . "']"
                    );
                foreach ($xPath as $child) {
                    $cleanXml->appendChild($child);
                }
            }
        }
        $layout->setXml($cleanXml);
        $layout->generateBlocks();
        return $this->_helperMessages
        ->initLayoutMessages($layout);
    }

    protected function _replaceFormKey()
    {
        $coreSession = $this->_coreSession;
        $formKey = $coreSession->getFormKey();
        if ($formKey) {
            $this->_placeholder[] = self::FORM_KEY_PLACEHOLDER;
            $this->_html[] = $formKey;
        }
    }

    /**
     * @param Mage_Core_Model_Layout $layout
     * @param Mage_Customer_Model_Session $session
     * @param array $dynamicBlocks
     * @param array $lazyBlocks
     */
    protected function _insertDynamicBlocks(
        \Magento\Framework\View\Layout &$layout,
        \Magento\Customer\Model\Session &$session,
        array &$dynamicBlocks,
        array &$lazyBlocks
        )
    {
        foreach ($dynamicBlocks as $blockName) {
            $block = $layout->getBlock($blockName);
            if ($block) {
                $this->_placeholder[] = $this->_helperBlock
                ->getPlaceholderHtml($blockName);
                $html = $block->toHtml();
                if (in_array($blockName, $lazyBlocks)) {
                    $session->setData('fpc_lazy_block_' . $blockName, $html);
                }
                $this->_html[] = $html;
            }
        }
    }

    public function getConfigs($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }

    /**
     */
    function __destruct()
    {}
}

