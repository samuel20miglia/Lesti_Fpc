<?php
declare(strict_types=1);
/**
 * Copyright © Lesti, All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lesti\Fpc\Helper\Block;

use Magento\Contact\Model\ConfigInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Contact base helper
 *
 * @deprecated 100.2.0
 * @see \Magento\Contact\Model\ConfigInterface
 */
class Messages extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var array
     */
    private $postData = null;

    protected $_registry;

    protected $request;

    protected $response;

    protected $_storeManager;

    /**
   * @var EventManager
   */
    protected $_eventManager;

    protected $_attributeFactory;

    protected $cache;

    protected $_themeProvider;

    protected $messageManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerViewHelper $customerViewHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory,
        \Magento\Framework\App\Cache $cache,
        \Magento\Framework\View\Design\Theme\ThemeProviderInterface $themeProvider,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_customerSession = $customerSession;
        $this->_registry = $registry;
        $this->request = $request;
        $this->response = $response;
        $this->_storeManager = $storeManager;
        $this->_eventManager = $eventManager;
        $this->_attributeFactory = $attributeFactory;
        $this->cache = $cache;
        $this->_themeProvider = $themeProvider;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }

    public function getCSStoreConfigs($path)
    {
        $configs = $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($configs) {
            return array_unique(array_map('trim', explode(',', $configs)));
        }

        return array();
    }

    public function getConfigs($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function initLayoutMessages(
        \Magento\Framework\View\Layout $layout
    )
    {
        $block = $layout->getMessagesBlock();
        if ($block) {
            $block->addMessages($this->messageManager->getMessages(true));
            $block->setEscapeMessageFlag(
                $storage->getEscapeMessages(true)
            );
        }
        return $layout;
    }
}