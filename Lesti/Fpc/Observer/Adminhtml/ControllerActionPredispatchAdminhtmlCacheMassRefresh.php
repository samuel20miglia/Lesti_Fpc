<?php
namespace Lesti\Fpc\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author samuel vegans
 *
 */
class ControllerActionPredispatchAdminhtmlCacheMassRefresh implements ObserverInterface
{

    const CACHE_TYPE = 'fpc';

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

    public function __construct(\Magento\Framework\App\Request\Http $request, \Lesti\Fpc\Model\Fpc $fpc)
    {
        $this->request = $request;
        $this->fpc = $fpc;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Magento\Framework\Event\ObserverInterface::execute()
     */
    public function execute(Observer $observer)
    {
        $types = $this->request->getParam('types');
        if ($this->fpc->isActive()) {
            if ((is_array($types) && in_array(self::CACHE_TYPE, $types)) || $types == self::CACHE_TYPE) {
                $this->fpc->clean();
            }
        }
    }
}

