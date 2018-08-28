<?php
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author samuel vegans
 *
 */
class CataloginventoryStockItemSaveAfter implements ObserverInterface
{

    /**
     *
     * @var \Lesti\Fpc\Model\Fpc
     *
     *
     */
    protected $fpc;

    /**
     *
     * @param \Lesti\Fpc\Model\Fpc $fpc
     */
    public function __construct(\Lesti\Fpc\Model\Fpc $fpc)
    {
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
        $item = $observer->getEvent()->getItem();
        if ($item->getStockStatusChangedAuto()) {
            $this->fpc->clean(sha1('product_' . $item->getProductId()));
        }
    }
}

