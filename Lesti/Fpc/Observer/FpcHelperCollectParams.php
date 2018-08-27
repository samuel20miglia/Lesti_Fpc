<?php
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author vegans
 *
 */
class FpcHelperCollectParams implements ObserverInterface
{

    const XML_PATH_SESSION_PARAMS = 'system/fpc/session_params';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $_helperData;

    protected $_design;

    protected $_catSession;

    /**
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Lesti\Fpc\Model\Fpc $fpc
     * @param \Lesti\Fpc\Helper\Data $helperData
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Magento\Catalog\Model\Session $catSession
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Lesti\Fpc\Model\Fpc $fpc,
        \Lesti\Fpc\Helper\Data $helperData,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Catalog\Model\Session $catSession
        ) {
            $this->storeManager = $storeManager;
            $this->fpc = $fpc;
            $this->_helperData = $helperData;
            $this->_design = $design;
            $this->_catSession = $catSession;
    }
    /**
     *
     * {@inheritdoc}
     *
     * @see \Magento\Framework\Event\ObserverInterface::execute()
     */
    public function execute(Observer $observer)
    {
        $params = array();
        // store
        $storeCode = $this->storeManager->getStore(true)->getCode();
        if ($storeCode) {
            $params['store'] = $storeCode;
        }
        // currency
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        if ($currencyCode) {
            $params['currency'] = $currencyCode;
        }
        // design
        $design = $this->_design;
        $params['design'] = $design->getPackageName().'_'.
            $design->getTheme('template');
            // session paramaters
            /** @var Lesti_Fpc_Helper_Data $helper */
            $helper = $this->_helperData;
            if ($helper->getFullActionName() === 'catalog_category_view') {
                $sessionParams = $this->_getSessionParams();
                $catalogSession = $this->_catSession;
                foreach ($sessionParams as $param) {
                    if ($data = $catalogSession->getData($param)) {
                        $params['session_' . $param] = $data;
                    }
                }
            }

            $parameters = $observer->getEvent()->getParameters();
            $additionalParams = $parameters->getValue();
            $additionalParams = array_merge($additionalParams, $params);
            $parameters->setValue($additionalParams);
    }

    /**
     *
     * @return array
     */
    protected function _getSessionParams()
    {
        $helper = $this->_helperData;
        return $helper->getCSStoreConfigs(self::XML_PATH_SESSION_PARAMS);
    }
}

