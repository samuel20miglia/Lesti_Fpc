<?php
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author vegans
 *
 */
class CatalogCategorySaveAfter implements ObserverInterface
{

    /**
     *
     * @var \Lesti\Fpc\Model\Fpc
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
        if ($this->fpc->isActive()) {
            $category = $observer->getEvent()->getCategory();
            if ($category->getId()) {
                $this->fpc->clean(sha1('category_' . $category->getId()));
            }
        }
    }
}

