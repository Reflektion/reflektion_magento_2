<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  For Cron weekday values
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class Weekday implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '*', 'label' => __('Every weekday (*)')],
            ['value' => '*/2', 'label' => __('Every 2 weekday (*/2)')],
            ['value' => '*/3', 'label' => __('Every 3 weekday (*/3)')],
            ['value' => '*/4', 'label' => __('Every 4 weekday (*/4)')],
            ['value' => '*/5', 'label' => __('Every 5 weekday (*/5)')],
            ['value' => '*/6', 'label' => __('Every 6 weekday (*/6)')],
            ['value' => '0', 'label' => __('Sunday')],
            ['value' => '1', 'label' => __('Monday')],
            ['value' => '2', 'label' => __('Tuesday')],
            ['value' => '3', 'label' => __('Wednesday')],
            ['value' => '4', 'label' => __('Thursday')],
            ['value' => '5', 'label' => __('Friday')],
            ['value' => '6', 'label' => __('Saturday')],
        ];
    }
}
