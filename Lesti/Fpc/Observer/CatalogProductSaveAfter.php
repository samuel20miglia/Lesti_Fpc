<?php
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author samuel vegans
 *
 */
class CatalogProductSaveAfter implements ObserverInterface
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
     * @param
     *            $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->fpc->isActive()) {
            $product = $observer->getEvent()->getProduct();
            if ($product->getId()) {
                $this->fpc->clean(sha1('product_' . $product->getId()));

                $origData = $product->getOrigData();
                if (empty($origData) || (! empty($origData) && $product->dataHasChangedFor('status'))) {
                    $categories = $product->getCategoryIds();
                    foreach ($categories as $categoryId) {
                        $this->fpc->clean(sha1('category_' . $categoryId));
                    }
                }
            }
        }
    }
}

