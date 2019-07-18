<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    23/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 */

namespace Reflektion\Catalogexport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Job extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('reflektion_job', 'job_id');
    }
}
