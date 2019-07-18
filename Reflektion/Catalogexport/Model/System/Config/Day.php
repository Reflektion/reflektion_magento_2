<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  For Cron day values
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class Day implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '*', 'label' => __('Every day (*)')],
            ['value' => '*/2', 'label' => __('Every 2 day (*/2)')],
            ['value' => '*/3', 'label' => __('Every 3 day (*/3)')],
            ['value' => '*/4', 'label' => __('Every 4 day (*/4)')],
            ['value' => '*/5', 'label' => __('Every 5 day (*/5)')],
            ['value' => '*/6', 'label' => __('Every 6 day (*/6)')],
            ['value' => '*/7', 'label' => __('Every 7 day (*/7)')],
            ['value' => '*/8', 'label' => __('Every 8 day (*/8)')],
            ['value' => '*/9', 'label' => __('Every 9 day (*/9)')],
            ['value' => '*/10', 'label' => __('Every 10 day (*/10)')],
            ['value' => '*/11', 'label' => __('Every 11 day (*/11)')],
            ['value' => '*/12', 'label' => __('Every 12 day (*/12)')],
            ['value' => '*/13', 'label' => __('Every 13 day (*/13)')],
            ['value' => '*/14', 'label' => __('Every 14 day (*/14)')],
            ['value' => '*/15', 'label' => __('Every 15 day (*/15)')],
            ['value' => '*/16', 'label' => __('Every 16 day (*/16)')],
            ['value' => '*/17', 'label' => __('Every 17 day (*/17)')],
            ['value' => '*/18', 'label' => __('Every 18 day (*/18)')],
            ['value' => '*/19', 'label' => __('Every 19 day (*/19)')],
            ['value' => '*/20', 'label' => __('Every 20 day (*/20)')],
            ['value' => '*/21', 'label' => __('Every 21 day (*/21)')],
            ['value' => '*/22', 'label' => __('Every 22 day (*/22)')],
            ['value' => '*/23', 'label' => __('Every 23 day (*/23)')],
            ['value' => '*/24', 'label' => __('Every 24 day (*/24)')],
            ['value' => '*/25', 'label' => __('Every 25 day (*/25)')],
            ['value' => '*/26', 'label' => __('Every 26 day (*/26)')],
            ['value' => '*/27', 'label' => __('Every 27 day (*/27)')],
            ['value' => '*/28', 'label' => __('Every 28 day (*/28)')],
            ['value' => '*/29', 'label' => __('Every 29 day (*/29)')],
            ['value' => '*/30', 'label' => __('Every 30 day (*/30)')],
            ['value' => '1', 'label' => __('At date 1')],
            ['value' => '2', 'label' => __('At date 2')],
            ['value' => '3', 'label' => __('At date 3')],
            ['value' => '4', 'label' => __('At date 4')],
            ['value' => '5', 'label' => __('At date 5')],
            ['value' => '6', 'label' => __('At date 6')],
            ['value' => '7', 'label' => __('At date 7')],
            ['value' => '8', 'label' => __('At date 8')],
            ['value' => '9', 'label' => __('At date 9')],
            ['value' => '10', 'label' => __('At date 10')],
            ['value' => '11', 'label' => __('At date 11')],
            ['value' => '12', 'label' => __('At date 12')],
            ['value' => '13', 'label' => __('At date 13')],
            ['value' => '14', 'label' => __('At date 14')],
            ['value' => '15', 'label' => __('At date 15')],
            ['value' => '16', 'label' => __('At date 16')],
            ['value' => '17', 'label' => __('At date 17')],
            ['value' => '18', 'label' => __('At date 18')],
            ['value' => '19', 'label' => __('At date 19')],
            ['value' => '20', 'label' => __('At date 20')],
            ['value' => '21', 'label' => __('At date 21')],
            ['value' => '22', 'label' => __('At date 22')],
            ['value' => '23', 'label' => __('At date 23')],
            ['value' => '24', 'label' => __('At date 24')],
            ['value' => '25', 'label' => __('At date 25')],
            ['value' => '26', 'label' => __('At date 26')],
            ['value' => '27', 'label' => __('At date 27')],
            ['value' => '28', 'label' => __('At date 28')],
            ['value' => '29', 'label' => __('At date 29')],
            ['value' => '30', 'label' => __('At date 30')],
            ['value' => '31', 'label' => __('At date 31')],
        ];
    }
}
