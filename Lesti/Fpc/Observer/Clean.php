<?php
declare(strict_types=1);

namespace Lesti\Fpc\Observer;
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

class Clean  implements ObserverInterface
{
    const CACHE_TYPE = 'fpc';

    protected $fpc;

    public function __construct(
        //Module $moduleHelper,
        //\Magento\Framework\Module\Manager $moduleManager,
        //\Magento\Framework\View\Asset\Repository $assetRepo
        \Lesti\Fpc\Model\Fpc $fpc
        ) {
            $this->fpc = $fpc;
            //$this->moduleManager = $moduleManager;
            //$this->assetRepo = $assetRepo;
    }
    /**
     * Cron job method to clean old cache resources
     */
    public function coreCleanCache()
    {
        $this->fpc->getFrontend()->clean(\Zend_Cache::CLEANING_MODE_OLD);
    }

    public function adminhtmlCacheFlushAll()
    {
        $this->fpc->clean();
    }

    public function controllerActionPredispatchAdminhtmlCacheMassRefresh()
    {
        $types = $this->getRequest()->getParam('types');
        if ($this->fpc->isActive()) {
            if ((is_array($types) && in_array(self::CACHE_TYPE, $types)) ||
                $types == self::CACHE_TYPE) {
                $this->fpc->clean();
            }
        }
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

    }
}
