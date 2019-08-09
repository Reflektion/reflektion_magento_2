<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    16/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Generate Jobs for Feed for all websites
 */
namespace Reflektion\Catalogexport\Controller\Adminhtml\Export;

use Magento\Backend\App\Action\Context;
use Reflektion\Catalogexport\Helper\Data;
use Reflektion\Catalogexport\Logger\Logger;
use Reflektion\Catalogexport\Model\Job;

class Exportall extends \Magento\Backend\App\Action
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

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Reflektion\Catalogexport\Helper\Data $rfkHelper
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     * @param \Reflektion\Catalogexport\Model\Job $job
     */
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
            $this->rfkHelper->validateFeedConfiguration();
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            // Redirect
            $this->_redirect("*/*/index");
            return;
        }
        try {
            $this->logger->info("Scheduling immediate baseline data feeds for all websites.");
            // Schedule all feeds for site
            $this->job->scheduleJobsAllWebsites(true);
            $this->logger->info("Successfully scheduled feeds. ");
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage());
            $this->logger->info("Failed to schedule feeds");
            $this->logger->info($e->getMessage());
        }
        $this->messageManager->addSuccess("Feed generation and transfer has been scheduled for all websites.");
        $this->_redirect("*/*/index");
    }
}
