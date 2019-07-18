<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    13/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Delete All the Selected Jobs
 */
namespace Reflektion\Catalogexport\Controller\Adminhtml\Job;

use Magento\Backend\App\Action\Context;
use Reflektion\Catalogexport\Logger\Logger;
use Reflektion\Catalogexport\Model\Job;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Reflektion\Catalogexport\Model\Job
     */
    protected $job;

    public function __construct(
        Context $context,
        Logger $logger,
        Job $job
    ) {
        $this->logger = $logger;
        $this->job = $job;
        parent::__construct($context);
    }

    public function execute()
    {
        $jobIds = $this->getRequest()->getParam('job_id');
        $logger = $this->logger;
        if (!is_array($jobIds)) {
            $this->messageManager->addError('Please select jobs(s) to delete.');
        } else {
            try {
                $jobModel = $this->job->getCollection()
                    ->addFieldToFilter('job_id', ['in' => $jobIds]);
                foreach ($jobModel as $jobs) {
                    $logger->info('Mass delete - Deleting job id ' . $jobs->getJobId());
                    $jobs->delete();
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->messageManager->addSuccess(
            __('Total of %1 record(s) were deleted.', count($jobIds))
        );
        $this->_redirect("*/*/index");
    }
}
