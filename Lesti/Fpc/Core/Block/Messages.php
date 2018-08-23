<?php
declare(strict_types=1);

namespace Lesti\Fpc\Core\Block;
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

/**
 * Class Lesti_Fpc_Core_Block_Messages
 */
class Messages extends \Magento\Framework\Message\ManagerInterface
{
    /**
     * Retrieve messages in HTML format grouped by type
     *
     * @param   string $type
     * @return  string
     */
    public function getGroupedHtml()
    {
        $html = parent::getGroupedHtml();

        /**
         * Use single transport object instance for all blocks
         */

        $_transportObject = new \Magento\Framework\DataObject();
        $_transportObject->setHtml($html);
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $manager = $om->get('Magento\Framework\Event\ManagerInterface');
        $manager->dispatch('core_block_messages_get_grouped_html_after',
            ['block' => $this, 'transport' => $_transportObject]
           );

        $html = $_transportObject->getHtml();

        return $html;
    }
}