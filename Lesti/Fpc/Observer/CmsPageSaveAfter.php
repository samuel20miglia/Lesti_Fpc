<?php
namespace Lesti\Fpc\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 *
 * @author samuel vegans
 *
 */
class CmsPageSaveAfter implements ObserverInterface
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
            $page = $observer->getEvent()->getObject();
            if ($page->getId()) {
                $tags = [
                    sha1('cms_' . $page->getId()),
                    sha1('cms_' . $page->getIdentifier())
                ];
                $this->fpc->clean($tags, \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG);
            }
        }
    }
}

