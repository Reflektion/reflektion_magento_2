<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    07/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Generate Jobs for Feed
 */
namespace Reflektion\Catalogexport\Controller\Adminhtml\Export;

use Magento\Backend\App\Action\Context;
use Reflektion\Catalogexport\Helper\Data;
use Reflektion\Catalogexport\Logger\Logger;
use Reflektion\Catalogexport\Model\Job;

class Exportone extends \Magento\Backend\App\Action
{
    /**
     * @param \Reflektion\Catalogexport\Helper\Data
     */
    protected $rfkHelper;
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
        Data $rfkHelper,
        Logger $logger,
        Job $job
    ) {
        $this->rfkHelper = $rfkHelper;
        $this->logger = $logger;
        $this->job = $job;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam("id");
            $this->rfkHelper->validateFeedConfiguration($id);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            // Redirect
            $this->_redirect("*/*/index");
            return;
        }
        try {
            $id = $this->getRequest()->getParam("id");
            $this->logger->info("Scheduling immediate baseline data feeds for website Id: " . $id);

            // Schedule all feeds for site
            $this->job->scheduleJobs($id, true);
            $this->logger->info("Successfully scheduled feeds. ");
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage());
            $this->logger->info("Failed to schedule feeds");
            $this->logger->info($e->getMessage());
        }
        $this->messageManager->addSuccess("Feed generation and transfer has been scheduled for website id ".$id);
        $this->_redirect("*/*/index");
    }
}
