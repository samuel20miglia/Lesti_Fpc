<?php
declare(strict_types = 1);
namespace Lesti\Fpc\Observer;

/**
 * Lesti_Fpc (http:gordonlesti.com/lestifpc)
 *
 * PHP version 7
 *
 * @link https://github.com/GordonLesti/Lesti_Fpc
 * @package Lesti_Fpc
 * @author Gordon Lesti <info@gordonlesti.com>
 * @copyright Copyright (c) 2013-2016 Gordon Lesti (http://gordonlesti.com)
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

/**
 * Class Parameters
 */
class Parameters implements ObserverInterface
{

    const XML_PATH_SESSION_PARAMS = 'system/fpc/session_params';

    protected $fpc;

    protected $_helperData;

    protected $_storeManager;

    protected $_design;

    protected $_catSession;

    public function __construct(
        // Module $moduleHelper,
        // \Magento\Framework\Module\Manager $moduleManager,
        // \Magento\Framework\View\Asset\Repository $assetRepo
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
        // $this->assetRepo = $assetRepo;
    }

    /**
     *
     * @param
     *            $observer
     */
    public function fpcHelperCollectParams($observer)
    {
        $params = array();
        // store
        $storeCode = $this->_storeManager->getStore(true)->getCode();
        if ($storeCode) {
            $params['store'] = $storeCode;
        }
        // currency
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        if ($currencyCode) {
            $params['currency'] = $currencyCode;
        }
        // design
        $design = $this->_design;
        $params['design'] = $design->getPackageName() . '_' . $design->getTheme('template');
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
