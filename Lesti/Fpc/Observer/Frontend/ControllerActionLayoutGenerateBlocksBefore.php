<?php
namespace Lesti\Fpc\Observer\Frontend;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Session;

/**
 *
 * @author vegans
 *
 */
class ControllerActionLayoutGenerateBlocksBefore implements ObserverInterface
{

    const CUSTOMER_SESSION_REGISTRY_KEY = 'fpc_customer_session';
    const SHOW_AGE_XML_PATH = 'system/fpc/show_age';
    const FORM_KEY_PLACEHOLDER = '<!-- fpc form_key_placeholder -->';
    const SESSION_ID_PLACEHOLDER = '<!-- fpc session_id_placeholder -->';

    protected $_cached = false;
    protected $_html = [];
    protected $_placeholder = [];
    protected $_cacheTags = [];

    /**
     *
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     *
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;

    /**
     *
     * @var \Lesti\Fpc\Model\Fpc
     *
     */
    protected $fpc;

    protected $_helperData;

    protected $_helperBlock;

    protected $_helperMessages;

    /**
     *
     * @var Session
     */
    protected $session;

    protected $_coreSession;
    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Lesti\Fpc\Model\Fpc $fpc
     * @param \Lesti\Fpc\Helper\Data $helperData
     * @param \Lesti\Fpc\Helper\Block $helperBlock
     * @param Session $customerSession
     * @param \Lesti\Fpc\Helper\Block\Messages $helperMessages
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\Http $response,
        \Lesti\Fpc\Model\Fpc $fpc,
        \Lesti\Fpc\Helper\Data $helperData,
        \Lesti\Fpc\Helper\Block $helperBlock,
        Session $customerSession,
        \Lesti\Fpc\Helper\Block\Messages $helperMessages,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->request = $request;
        $this->response = $response;
        $this->fpc = $fpc;
        $this->_helperData = $helperData;
        $this->_helperBlock = $helperBlock;
        $this->_helperMessages = $helperMessages;
        $this->_coreSession = $coreSession;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Magento\Framework\Event\ObserverInterface::execute()
     */
    public function execute(Observer $observer)
    {

        if ($this->fpc->isActive() && ! $this->_cached && $this->_helperData->canCacheRequest()) {
            $key = $this->_helperData->getKey();
            if ($object = $this->fpc->load($key)) {
                $time = $object->getTime();
                $body = $object->getContent();
                $this->_cached = true;
                $session = $this->session;
                $lazyBlocks = $this->_helperBlock->getLazyBlocks();
                $dynamicBlocks = $this->_helperBlock->getDynamicBlocks();
                $blockHelper = $this->_helperBlock;
                if ($blockHelper->areLazyBlocksValid()) {
                    foreach ($lazyBlocks as $blockName) {
                        $this->_placeholder[] = $blockHelper->getPlaceholderHtml($blockName);
                        $this->_html[] = $session->getData('fpc_lazy_block_' . $blockName);
                    }
                } else {
                    $dynamicBlocks = array_merge($dynamicBlocks, $lazyBlocks);
                }
                // prepare Layout
                $layout = $this->_prepareLayout($observer->getEvent()
                    ->getLayout(), $dynamicBlocks);
                // insert dynamic blocks
                $this->_insertDynamicBlocks($layout, $session, $dynamicBlocks, $lazyBlocks);
                $this->_placeholder[] = self::SESSION_ID_PLACEHOLDER;
                $this->_html[] = $session->getEncryptedSessionId();
                $this->_replaceFormKey();
                $body = str_replace($this->_placeholder, $this->_html, $body);
                if ($this->scopeConfig->getValue(self::SHOW_AGE_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                    $this->response->setHeader('Age', time() - $time);
                }
                $this->response->setHeader('Content-Type', $object->getContentType(), true);
                $this->response->setBody($body);
                $this->eventManager->dispatch('fpc_http_response_send_before', array(
                    'response' => $this->response
                ));
                $this->response->sendResponse();
                exit();
            }
            if ($this->scopeConfig->getValue(self::SHOW_AGE_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                $this->response->setHeader('Age', 0);
            }
        }
    }

    protected function _prepareLayout(
        \Magento\Framework\View\LayoutInterface $layout,
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

    protected function _replaceFormKey()
    {
        $coreSession = $this->_coreSession;
        $formKey = $coreSession->getFormKey();
        if ($formKey) {
            $this->_placeholder[] = self::FORM_KEY_PLACEHOLDER;
            $this->_html[] = $formKey;
        }
    }
}

