<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    22/11/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Add options to dropdown - admin panel
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class Rfkcat implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Reflektion\Catalogexport\Plugin\Category::CATEGORY_RFK_1,
                'label' => __('Add query parameter rfk=1')
            ],
            [
                'value' => \Reflektion\Catalogexport\Plugin\Category::CATEGORY_RFK_URI,
                'label' => __('Add KEYWORD to path')
            ],
            [
                'value' => \Reflektion\Catalogexport\Plugin\Category::CATEGORY_RFK_SUBDOMAIN,
                'label' => __('Add Sub-Domain before path')
            ],
        ];
    }
}
