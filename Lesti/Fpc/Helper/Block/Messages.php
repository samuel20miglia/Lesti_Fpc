<?php
/**
 * Lesti_Fpc (http:gordonlesti.com/lestifpc)
 *
 * PHP version 5
 *
 * @link      https://github.com/GordonLesti/Lesti_Fpc
 * @package   Lesti_Fpc
 * @author    Gordon Lesti <info@gordonlesti.com>
 * @copyright Copyright (c) 2013-2016 Gordon Lesti (http://gordonlesti.com)
 * @license   http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace Lesti\Fpc\Helper\Block;

/**
 * Class Lesti_Fpc_Helper_Block_Messages
 */
class Messages extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct(
            $context
        );
    }

    /**
     * @param $layout
     * @return mixed
     */
    public function initLayoutMessages(
        \Magento\Framework\View\LayoutInterface $layout,
        $messagesStorage =
        array('catalog/session', 'tag/session', 'checkout/session', 'customer/session')
    )
    {
        $block = $layout->getMessagesBlock();
        if ($block) {
            foreach ($messagesStorage as $storageName) {
                $storage = Mage::getSingleton($storageName);
                if ($storage) {
                    $block->addMessages($storage->getMessages(true));
                    $block->setEscapeMessageFlag(
                        $storage->getEscapeMessages(true)
                    );
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        Mage::helper('core')->__(
                            'Invalid messages storage "%s" for layout '.
                            'messages initialization',
                            (string)$storageName
                        )
                    );
                }
            }
        }
        return $layout;
    }
}
