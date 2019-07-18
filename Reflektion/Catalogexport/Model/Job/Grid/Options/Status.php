<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    18/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Show all the available Job Status in grid filter
 */

namespace Reflektion\Catalogexport\Model\Job\Grid\Options;

class Status implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $status = [
            \Reflektion\Catalogexport\Model\Job::STATUS_SCHEDULED => __('Scheduled'),
            \Reflektion\Catalogexport\Model\Job::STATUS_RUNNING => __('Running'),
            \Reflektion\Catalogexport\Model\Job::STATUS_COMPLETED => __('Completed'),
            \Reflektion\Catalogexport\Model\Job::STATUS_ERROR => __('Error'),
            \Reflektion\Catalogexport\Model\Job::STATUS_MANUAL => __('Manual'),
        ];
        return $status;
    }
}
