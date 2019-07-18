<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  For Cron month values
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class Month implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '*', 'label' => __('Every month (*)')],
            ['value' => '*/2', 'label' => __('Every 2 months (*/2)')],
            ['value' => '*/3', 'label' => __('Every 3 months (*/2)')],
            ['value' => '*/4', 'label' => __('Every 4 months (*/2)')],
            ['value' => '*/5', 'label' => __('Every 5 months (*/2)')],
            ['value' => '*/6', 'label' => __('Every 6 months (*/2)')],
            ['value' => '*/7', 'label' => __('Every 7 months (*/2)')],
            ['value' => '*/8', 'label' => __('Every 8 months (*/2)')],
            ['value' => '*/9', 'label' => __('Every 9 months (*/2)')],
            ['value' => '*/10', 'label' => __('Every 10 months (*/2)')],
            ['value' => '*/11', 'label' => __('Every 11 months (*/2)')],
            ['value' => '1', 'label' => __('January')],
            ['value' => '2', 'label' => __('February')],
            ['value' => '3', 'label' => __('March')],
            ['value' => '4', 'label' => __('April')],
            ['value' => '5', 'label' => __('May')],
            ['value' => '6', 'label' => __('June')],
            ['value' => '7', 'label' => __('July')],
            ['value' => '8', 'label' => __('August')],
            ['value' => '9', 'label' => __('September')],
            ['value' => '10', 'label' => __('October')],
            ['value' => '11', 'label' => __('November')],
            ['value' => '12', 'label' => __('December')],
        ];
    }
}
