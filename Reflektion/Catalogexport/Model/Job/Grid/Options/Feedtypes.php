<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    13/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Show all the available Feed Types in grid filter
 */

namespace Reflektion\Catalogexport\Model\Job\Grid\Options;

class Feedtypes implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $feedTypes = [
            \Reflektion\Catalogexport\Model\Job::TYPE_GENERATE_BASELINE => __('Manual Feed'),
            \Reflektion\Catalogexport\Model\Job::TYPE_GENERATE_DAILY => __('Daily Feed'),
            \Reflektion\Catalogexport\Model\Job::TYPE_TRANSFER => __('Transfer File'),
            \Reflektion\Catalogexport\Model\Job::TYPE_TRANSFER_MANUAL => __('Transfer File Manual'),
        ];
        return $feedTypes;
    }
}
