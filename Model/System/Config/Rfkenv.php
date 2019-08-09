<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    12/01/18
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Add options to dropdown - admin panel
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class Rfkenv implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'test',
                'label' => __('Test')
            ],
            [
                'value' => 'staging',
                'label' => __('Staging')
            ],
            [
                'value' => 'uat',
                'label' => __('UAT')
            ],
            [
                'value' => 'live',
                'label' => __('Live')
            ],
        ];
    }
}
