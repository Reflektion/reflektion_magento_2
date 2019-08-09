<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To enable or disable module and feed generation
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class EnableToggle implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'enabled', 'label' => __('Enabled')],
            ['value' => 'disabled', 'label' => __('Disabled')],
        ];
    }
}
