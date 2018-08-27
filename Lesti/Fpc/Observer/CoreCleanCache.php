<?php
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author vegans
 *
 */
class CoreCleanCache implements ObserverInterface
{

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
        $this->fpc->getFrontend()->clean(\Zend_Cache::CLEANING_MODE_OLD);
    }
}

