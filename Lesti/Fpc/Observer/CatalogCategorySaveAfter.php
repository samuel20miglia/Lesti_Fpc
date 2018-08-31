<?php
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;

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
     * @var use Magento\Framework\Registry;
     */
    protected $registry;

    /**
     *
     * @param \Lesti\Fpc\Model\Fpc $fpc
     */
    public function __construct(
        \Lesti\Fpc\Model\Fpc $fpc,
        \Magento\Framework\Registry $registry)
    {
        $this->fpc = $fpc;
        $this->registry = $registry;
        parent::__construct($this->registry);
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

