<?php
namespace Lesti\Fpc\Observer\Frontend;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Session;
use Lesti\Fpc\Model\Fpc\CacheItem;

/**
 *
 * @author vegans
 *
 */
class HttpResponseSendBefore implements ObserverInterface
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

    /**
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    protected $_coreSession;

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
        \Lesti\Fpc\Model\Fpc $fpc,
        \Lesti\Fpc\Helper\Data $helperData,
        \Lesti\Fpc\Helper\Block $helperBlock,
        Session $customerSession,
        \Lesti\Fpc\Helper\Block\Messages $helperMessages,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Session\SessionManagerInterface $coreSession)
    {
        $this->request = $request;
        $this->fpc = $fpc;
        $this->_helperData = $helperData;
        $this->_helperBlock = $helperBlock;
        $this->_helperMessages = $helperMessages;
        $this->_eventManager = $eventManager;
        $this->_coreSession = $coreSession;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Magento\Framework\Event\ObserverInterface::execute()
     */
    public function execute(Observer $observer)
    {
        $response = $observer->getControllerAction()->getResponse();
        if ($this->_getFpc()->isActive() && ! $this->_cached && $this->_helperData->canCacheRequest() && $response->getHttpResponseCode() == 200) {
            $fullActionName = $this->_helperData->getFullActionName();
            $cacheableActions = $this->_helperData->getCacheableActions();
            if (in_array($fullActionName, $cacheableActions)) {
                $key = $this->_helperData->getKey();
                $body = $response->getBody();
                $session = $this->_coreSession;
                $formKey = $session->getFormKey();
                if ($formKey) {
                    $body = str_replace($formKey, self::FORM_KEY_PLACEHOLDER, $body);
                    $this->_placeholder[] = self::FORM_KEY_PLACEHOLDER;
                    $this->_html[] = $formKey;
                }
                $sid = $session->getEncryptedSessionId();
                if ($sid) {
                    $body = str_replace($sid, self::SESSION_ID_PLACEHOLDER, $body);
                    $this->_placeholder[] = self::SESSION_ID_PLACEHOLDER;
                    $this->_html[] = $sid;
                }
                // edit cacheTags via event
                $cacheTags = new \Magento\Framework\DataObject();
                $cacheTags->setValue($this->_cacheTags);
                $this->_eventManager->dispatch('fpc_observer_collect_cache_tags', array(
                    'cache_tags' => $cacheTags
                ));
                $this->_cacheTags = $cacheTags->getValue();
                $this->fpc->save(new CacheItem($body, time(), $this->_helperData->getContentType($response)), $key, $this->_cacheTags);
                $this->_cached = true;
                $body = str_replace($this->_placeholder, $this->_html, $body);
                $response->setBody($body);
            }
        }
    }
}

