<?php
namespace Lesti\Fpc\Observer\Frontend;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Session;

/**
 *
 * @author vegans
 *
 */
class ControllerActionPostdispatch implements ObserverInterface
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

    protected $_helperData;

    /**
     *
     * @var Session
     */
    protected $session;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Lesti\Fpc\Model\Fpc $fpc,
        \Lesti\Fpc\Helper\Data $helperData,
        Session $customerSession)
    {
        $this->request = $request;
        $this->fpc = $fpc;
        $this->_helperData = $helperData;
        $this->session = $customerSession;
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
            $fullActionName = $this->_helperData->getFullActionName();
            if (in_array($fullActionName, $this->_helperData->getRefreshActions())) {
                $session = $this->session;
                $session->setData(\Lesti\Fpc\Helper\Block::LAZY_BLOCKS_VALID_SESSION_PARAM, false);
            }
        }
    }
}

