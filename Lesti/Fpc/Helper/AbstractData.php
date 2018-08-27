<?php
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

namespace Lesti\Fpc\Helper;

/**
 * Class Lesti_Fpc_Helper_Abstract
 */
abstract class AbstractData extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct(
            $context
        );
    }

    /**
     * Returns comma seperated store configs as array
     *
     * @param $path
     * @param null $store
     * @return array
     */
    public function getCSStoreConfigs($path, $store = null)
    {
        $configs = trim($this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store));

        if ($configs) {
            return array_unique(array_map('trim', explode(',', $configs)));
        }

        return array();
    }
}
