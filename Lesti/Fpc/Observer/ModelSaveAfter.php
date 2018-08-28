<?php
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author vegans
 *
 */
class ModelSaveAfter implements ObserverInterface
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
        if ($this->fpc->isActive()) {
            $object = $observer->getEvent()->getObject();
            if ($object instanceof \Magento\Cms\Model\Block) {
                $this->_cmsBlockSaveAfter($object);
            } else {
                $dataObject = $object->getDataObject();
                if ($object->getType() === 'mass_action'
                    && $object->getEntity() === 'catalog_product'
                    && $dataObject instanceof \Magento\Catalog\Model\Product\Action) {
                    $this->_catalogProductSaveAfterMassAction($dataObject->getProductIds());
                }
            }
        }
    }

    /**
     *
     * @param \Magento\Cms\Model\Block $block
     */
    protected function _cmsBlockSaveAfter(\Magento\Cms\Model\Block $block)
    {
        $this->fpc->clean(sha1('cmsblock_' . $block->getIdentifier()));
    }

    /**
     *
     * @param array $productIds
     */
    protected function _catalogProductSaveAfterMassAction(array $productIds)
    {
        if (! empty($productIds)) {
            $tags = array();
            foreach ($productIds as $productId) {
                $tags[] = sha1('product_' . $productId);
            }
            $this->fpc->clean($tags);
        }
    }
}

