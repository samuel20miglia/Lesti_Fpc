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

namespace Lesti\Fpc\Model\System\Config\Source;

/**
 * Class Lesti_Fpc_Model_Adminhtml_System_Config_Source_Gzcompress
 */
class Gzcompress
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach ($this->toArray() as $key => $value) {
            $options[] = array('value' => $key, 'label' => $value);
        }

        return $options;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $options = array(-2 => __('No'));
        for ($i=0; $i <10; $i++) {
            $options[$i] = $i;
        }

        return $options;
    }
}
