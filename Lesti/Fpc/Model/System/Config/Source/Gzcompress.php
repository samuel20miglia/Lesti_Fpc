<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Lesti\Fpc\Model\System\Config\Source;

/**
 * @api
 * @since 100.0.2
 */
class Gzcompress implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
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
     * Get options in "key-value" format
     *
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