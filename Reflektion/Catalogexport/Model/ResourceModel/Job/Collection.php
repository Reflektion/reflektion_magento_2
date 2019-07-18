<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    23/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 */

namespace Reflektion\Catalogexport\Model\ResourceModel\Job;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Reflektion\Catalogexport\Model\Job',
            'Reflektion\Catalogexport\Model\ResourceModel\Job'
        );
    }
}
