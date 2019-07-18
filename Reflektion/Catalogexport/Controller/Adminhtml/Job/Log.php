<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To forward control to open download Logs form
 */

namespace Reflektion\Catalogexport\Controller\Adminhtml\Job;

use Magento\Backend\App\Action\Context;

class Log extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
