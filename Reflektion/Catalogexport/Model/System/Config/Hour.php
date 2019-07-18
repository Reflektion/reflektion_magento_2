<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  For Cron hour values
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class Hour implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '*', 'label' => __('Every hour (*)')],
            ['value' => '*/2', 'label' => __('Every 2 hours (*/2)')],
            ['value' => '*/3', 'label' => __('Every 3 hours (*/3)')],
            ['value' => '*/4', 'label' => __('Every 4 hours (*/4)')],
            ['value' => '*/5', 'label' => __('Every 5 hours (*/5)')],
            ['value' => '*/6', 'label' => __('Every 6 hours (*/6)')],
            ['value' => '*/7', 'label' => __('Every 7 hours (*/7)')],
            ['value' => '*/8', 'label' => __('Every 8 hours (*/8)')],
            ['value' => '*/9', 'label' => __('Every 9 hours (*/9)')],
            ['value' => '*/10', 'label' => __('Every 10 hours (*/10)')],
            ['value' => '*/11', 'label' => __('Every 11 hours (*/11)')],
            ['value' => '*/12', 'label' => __('Every 12 hours (*/12)')],
            ['value' => '*/13', 'label' => __('Every 13 hours (*/13)')],
            ['value' => '*/14', 'label' => __('Every 14 hours (*/14)')],
            ['value' => '*/15', 'label' => __('Every 15 hours (*/15)')],
            ['value' => '*/16', 'label' => __('Every 16 hours (*/16)')],
            ['value' => '*/17', 'label' => __('Every 17 hours (*/17)')],
            ['value' => '*/18', 'label' => __('Every 18 hours (*/18)')],
            ['value' => '*/19', 'label' => __('Every 19 hours (*/19)')],
            ['value' => '*/20', 'label' => __('Every 20 hours (*/20)')],
            ['value' => '*/21', 'label' => __('Every 21 hours (*/21)')],
            ['value' => '*/22', 'label' => __('Every 22 hours (*/22)')],
            ['value' => '*/23', 'label' => __('Every 23 hours (*/23)')],
            ['value' => '0', 'label' => __('At hour 0')],
            ['value' => '1', 'label' => __('At hour 1')],
            ['value' => '2', 'label' => __('At hour 2')],
            ['value' => '3', 'label' => __('At hour 3')],
            ['value' => '4', 'label' => __('At hour 4')],
            ['value' => '5', 'label' => __('At hour 5')],
            ['value' => '6', 'label' => __('At hour 6')],
            ['value' => '7', 'label' => __('At hour 7')],
            ['value' => '8', 'label' => __('At hour 8')],
            ['value' => '9', 'label' => __('At hour 9')],
            ['value' => '10', 'label' => __('At hour 10')],
            ['value' => '11', 'label' => __('At hour 11')],
            ['value' => '12', 'label' => __('At hour 12')],
            ['value' => '13', 'label' => __('At hour 13')],
            ['value' => '14', 'label' => __('At hour 14')],
            ['value' => '15', 'label' => __('At hour 15')],
            ['value' => '16', 'label' => __('At hour 16')],
            ['value' => '17', 'label' => __('At hour 17')],
            ['value' => '18', 'label' => __('At hour 18')],
            ['value' => '19', 'label' => __('At hour 19')],
            ['value' => '20', 'label' => __('At hour 20')],
            ['value' => '21', 'label' => __('At hour 21')],
            ['value' => '22', 'label' => __('At hour 22')],
            ['value' => '23', 'label' => __('At hour 23')],
        ];
    }
}
