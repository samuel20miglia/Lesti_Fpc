<?php
namespace Lesti\Fpc\Observer\Frontend;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author vegans
 *
 */
class CoreBlockAbstractToHtmlAfter implements ObserverInterface
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
     * @var \Lesti\Fpc\Model\Fpc
     *
     */
    protected $fpc;

    protected $_helperData;

    protected $_helperBlock;

    /**
     *
     * @param \Lesti\Fpc\Model\Fpc $fpc
     * @param \Lesti\Fpc\Helper\Data $helperData
     * @param \Lesti\Fpc\Helper\Block $helperBlock
     */
    public function __construct(\Lesti\Fpc\Model\Fpc $fpc, \Lesti\Fpc\Helper\Data $helperData, \Lesti\Fpc\Helper\Block $helperBlock)
    {
        $this->fpc = $fpc;
        $this->_helperData = $helperData;
        $this->_helperBlock = $helperBlock;
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
            $fullActionName = $this->_helperData->getFullActionName();
            $block = $observer->getEvent()->getBlock();
            $blockName = $block->getNameInLayout();
            $dynamicBlocks = $this->_helperBlock->getDynamicBlocks();
            $lazyBlocks = $this->_helperBlock->getLazyBlocks();
            $dynamicBlocks = array_merge($dynamicBlocks, $lazyBlocks);
            $cacheableActions = $this->_helperData->getCacheableActions();
            if (in_array($fullActionName, $cacheableActions)) {
                $this->_cacheTags = array_merge($this->_helperBlock->getCacheTags($block), $this->_cacheTags);
                if (in_array($blockName, $dynamicBlocks)) {
                    $placeholder = $this->_helperBlock->getPlaceholderHtml($blockName);
                    $html = $observer->getTransport()->getHtml();
                    $this->_html[] = $html;
                    $this->_placeholder[] = $placeholder;
                    $observer->getTransport()->setHtml($placeholder);
                }
            }
        }
    }
}

