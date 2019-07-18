<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    28/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Add options to dropdown - admin panel
 */
namespace Reflektion\Catalogexport\Model\System\Config;

class Rfkexp implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Reflektion\Catalogexport\Model\CatalogSearch\Layer::FRONTEND_JAVASCRIPT,
                'label' => __('Frontend JavaScript Integration')
            ],
            [
                'value' => \Reflektion\Catalogexport\Model\CatalogSearch\Layer::BACKEND_API,
                'label' => __('Backend API Integration')
            ],
        ];
    }
}
