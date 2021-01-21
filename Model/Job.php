<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    23/06/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Generate catalog product feeds output(CSV) in queue
 */
namespace Reflektion\Catalogexport\Model;

use Magento\Cron\Exception;
use Magento\Framework\Model\AbstractModel;
use Reflektion\Catalogexport\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Job extends AbstractModel
{
    /**
     *  Job Types
     */
    const TYPE_GENERATE_BASELINE = 1;
    const TYPE_GENERATE_DAILY = 2;
    const TYPE_TRANSFER = 3;
    const TYPE_TRANSFER_MANUAL = 4;

    /**
     *  Statuses
     */
    const STATUS_SCHEDULED = 1;
    const STATUS_RUNNING = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_ERROR = 4;
    const STATUS_MANUAL = 5;

    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $systemDateTime;
    /**
     * @var \Reflektion\Catalogexport\Model\Generatefeeds
     */
    protected $genModel;
    /**
     * @var \Reflektion\Catalogexport\Model\Transferfeeds
     */
    protected $tranModel;
    /**
     * @var \Magento\Cron\Model\Schedule
     */
    protected $cronSchedule;
    /**
     * @var \Magento\Catalog\Model\Session
     */
    protected $session;
    /**
     * @var \Reflektion\Catalogexport\Helper\Email
     */
    protected $emailHelper;
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Reflektion\Catalogexport\Model\ResourceModel\Job');
    }

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Reflektion\Catalogexport\Logger\Logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface
     * @param \Magento\Store\Model\StoreManagerInterface
     * @param \Magento\Framework\ObjectManagerInterface
     * @param \Magento\Framework\Stdlib\DateTime\DateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     * @param \Reflektion\Catalogexport\Model\Generatefeeds
     * @param \Reflektion\Catalogexport\Model\Transferfeeds
     * @param \Magento\Cron\Model\Schedule $schedule
     * @param \Reflektion\Catalogexport\Helper\Email
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ObjectManagerInterface $objectManager,
        DateTime $dateTime,
        TimezoneInterface $systemDateTime,
        Generatefeeds $generatefeeds,
        Transferfeeds $transferfeeds,
        \Magento\Cron\Model\Schedule $schedule,
        \Reflektion\Catalogexport\Helper\Email $emailHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Catalog\Model\Session $session,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->dateTime = $dateTime;
        $this->systemDateTime = $systemDateTime;
        $this->genModel = $generatefeeds;
        $this->tranModel = $transferfeeds;
        $this->cronSchedule = $schedule;
        $this->emailHelper = $emailHelper;
        $this->session = $session;
    }

    /**
     * Pull the next job to run from the queue and set status to running
     */
    public static function getNextJobFromQueue()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get("\Reflektion\Catalogexport\Logger\Logger");
        $logger->info("Getting next job from the queue.");
        $jobModel = $objectManager->create("Reflektion\Catalogexport\Model\Job");
        $collection = $jobModel->getResourceCollection();
        $table = $collection->getTable("reflektion_job");
        $collection->getSelect()
            ->where(
                'status = ' . \Reflektion\Catalogexport\Model\Job::STATUS_SCHEDULED
            )
            ->where(
                \Reflektion\Catalogexport\Model\Job::STATUS_SCHEDULED
                . " not in (select status from {$table} mbj2 where mbj2.job_id = main_table.dependent_on_job_id) "
            )
            ->order('job_id')
            ->limit(1);
        $dateTime = $objectManager->get("\Magento\Framework\Stdlib\DateTime\DateTime");
        foreach ($collection as $job) {
            $logger->info("Found job id: " . $job->getJobId());
            $job->setStatus(\Reflektion\Catalogexport\Model\Job::STATUS_RUNNING);
            $job->setStartedAt($dateTime->gmtDate());
            $job->save();
            return $job;
        }
        $logger->info("Found job id: ");
        return false;
    }

    /**
     * Create a new job object
     */
    public function createJob($dependentOnJobId, $websiteId, $type, $feedType, $isBaseTran = 0)
    {
        if (self::TYPE_GENERATE_BASELINE == $type) {
            $status = self::STATUS_MANUAL;
        } elseif ($isBaseTran == 1) {
            $status = self::STATUS_MANUAL;
            $type = self::TYPE_TRANSFER_MANUAL;
        } else {
            $status = self::STATUS_SCHEDULED;
        }
        $this->logger->info("Scheduling new job.");
        $newJob = $this->objectManager->create("Reflektion\Catalogexport\Model\Job");
        $newJob->setDependentOnJobId($dependentOnJobId);
        $newJob->setMinEntityId(0);
        $newJob->setWebsiteId($websiteId);
        $newJob->setType($type);
        $newJob->setFeedType($feedType);
        $newJob->setScheduledAt($this->dateTime->gmtDate());
        $newJob->setStatus($status);
        $newJob->save();
        return $newJob;
    }

    /**
     * Schedule all the necessary daily jobs for today
     */
    public static function scheduleAllDailyJobs()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get("\Reflektion\Catalogexport\Logger\Logger");
        $storeManager = $objectManager->get("\Magento\Store\Model\StoreManagerInterface");
        $websites = $storeManager->getWebsites(false, true);
        /* new code as per cron configuration at website level */
        $jobModel = $objectManager->get("Reflektion\Catalogexport\Model\Job");
        $match = $jobModel->checkWebsiteCron();
        foreach ($websites as $website) {
            $checkWebFreq = isset($match[$website->getId()]) ? $match[$website->getId()] : false;
            if ($checkWebFreq !== false) {
                $jobModel->scheduleJobs($website->getId(), false);
                $logger->info(
                    'export for website with id ' .
                    $website->getId() . ' is completed'
                );
            }
        }
    }

    /**
     * Schedule all daily or baseline jobs for all websites to run immediately
     */
    public function scheduleJobsAllWebsites($bBaselineFile)
    {
        $websites = $this->storeManager->getWebsites(false, true);
        foreach ($websites as $website) {
            $websiteId = $website->getId();
            $sftpHost = $this->scopeConfig->getValue(
                'reflektion_datafeeds/connect/hostname',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $website->getCode()
            );
            if (!empty(trim($sftpHost))) {
                $this->scheduleJobs($websiteId, $bBaselineFile);
            }
        }
    }

    /**
     * Schedule baseline or incremental daily jobs to run immediately
     */
    public function scheduleJobs($websiteId, $bBaselineFile)
    {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        $this->logger->info("Scheduling jobs for website: " . $websiteId);
        $this->logger->info("All feeds for website set to: " .
            $this->scopeConfig->getValue(
                'reflektion_datafeeds/general/allfeedsenabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            ));
        $lastJobId = null;
        if ($this->scopeConfig->getValue(
                'reflektion_datafeeds/general/allfeedsenabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            ) != 'enabled') {
            return;
        }
        // Generate jobs - enabled feeds
        $isBaseTran = 0;
        foreach (Generatefeeds::getFeedTypes() as $curType) {
            if ($this->scopeConfig->getValue(
                    'reflektion_datafeeds/feedsenabled/' . $curType,
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                    $websiteCode
                ) == 'enabled') {
                // Check manual or daily
                $jobType = 0;
                if ($bBaselineFile) {
                    $jobType = self::TYPE_GENERATE_BASELINE;
                    $isBaseTran = 1;
                } else {
                    $jobType = self::TYPE_GENERATE_DAILY;
                    $isBaseTran = 0;
                }
                $job = $this->createJob($lastJobId, $websiteId, $jobType, $curType);
                $job->save();
                $lastJobId = $job->getJobId();
            }
        }
        // Transfer feeds job
        $job = $this->createJob($lastJobId, $websiteId, self::TYPE_TRANSFER, null, $isBaseTran);
        $job->save();
    }

    /**
     * Run job
     */
    public function run()
    {
        try {
            $this->logger->info('Running job: ' . $this->getJobId());
            $this->logger->info('Website Id: ' . $this->getWebsiteId());
            $this->logger->info('Dependent On Job Id: ' . $this->getDependentOnJobId());
            // Check if dependent job id is successfully executed
            if ($this->getFeedType() == "" && $this->getDependentOnJobId() != null 
                && $this->session->getdata("trigger_sftp_transfer") == 0 &&
                $this->getType() == \Reflektion\Catalogexport\Model\Job::TYPE_TRANSFER) 
            {
                $this->setStatus(\Reflektion\Catalogexport\Model\Job::STATUS_ERROR);
                $this->session->unsetData('trigger_sftp_transfer');
                $this->setEndedAt($this->dateTime->gmtDate());
                $this->setErrorMessage("Dependent Job failed");
                $this->save();
                $this->logger->info("Transfer Feed Job failed.");
                return $this;
            }
            $this->logger->info('Min Entity Id: ' . $this->getMinEntityId());
            $this->logger->info('Type : ' . $this->getType());
            $this->logger->info('Feed Type: ' . $this->getFeedType());
            $this->logger->info('Memory usage: ' . memory_get_usage());
            // Execute the job
            $this->executeJob();

            $this->logger->info('Job completed successfully.');
            $this->logger->info('Memory usage: ' . memory_get_usage());
        } catch (\Exception $e) {
            // Fail this job
            $this->setStatus(\Reflektion\Catalogexport\Model\Job::STATUS_ERROR);
            if ($this->session->getdata("trigger_sftp_transfer") == 1) {
                $this->session->setdata("trigger_sftp_transfer", 0);
            }
            $this->setEndedAt($this->dateTime->gmtDate());
            $this->setErrorMessage($e->getMessage());
            $this->save();

            //Send failure mail
            $this->emailHelper->emailError($e->getMessage(), $this->getWebsiteId(), $this->getJobId());

            // Log exception
            $this->logger->info($e->getMessage());
            $this->logger->info('Job failed with error:');
            $this->logger->info('Memory usage: ' . memory_get_usage());
        }
        return $this;
    }

    /**
     * Execute this job
     */
    protected function executeJob()
    {
        $websiteCode = $this->storeManager->getWebsite($this->getWebsiteId())->getCode();
        $this->logger->info("website code execute: " . $websiteCode);

        if ($this->scopeConfig->getValue(
                'reflektion_datafeeds/general/allfeedsenabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            ) != 'enabled') {
            throw new \Exception('Data feeds not enabled for website: ' . $this->getWebsiteId());
        }
        $bDone = false;

        // Switch on job type
        switch ($this->getType()) {
            case \Reflektion\Catalogexport\Model\Job::TYPE_GENERATE_BASELINE:
                // Call - \Reflektion\Catalogexport\Model\Generatefeeds
                $minEntityId = $this->getMinEntityId();
                $this->genModel->generateForWebsite(
                    $this->getWebsiteId(),
                    true,
                    $this->getFeedType(),
                    $minEntityId,
                    $bDone
                );
                $bDone = true;
                $this->setMinEntityId($minEntityId);
                break;
            case \Reflektion\Catalogexport\Model\Job::TYPE_GENERATE_DAILY:
                // Call - \Reflektion\Catalogexport\Model\Generatefeeds
                $minEntityId = $this->getMinEntityId();
                $this->genModel->generateForWebsite(
                    $this->getWebsiteId(),
                    false,
                    $this->getFeedType(),
                    $minEntityId,
                    $bDone
                );
                $bDone = true;
                $this->setMinEntityId($minEntityId);
                break;
            case \Reflektion\Catalogexport\Model\Job::TYPE_TRANSFER:
                // Call - \Reflektion\Catalogexport\Model\Transferfeeds
                $this->tranModel->transfer($this->getWebsiteId());
                $bDone = true;
                break;
            case \Reflektion\Catalogexport\Model\Job::TYPE_TRANSFER_MANUAL:
                // Call - \Reflektion\Catalogexport\Model\Transferfeeds
                if ($this->scopeConfig->getValue(
                    'reflektion_datafeeds/feedsenabled/feed_jobs_retry',
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                    $websiteCode
                ) != 'enabled') {
                    $this->session->setdata("retry_transfer", 1);
                }
                $this->tranModel->transfer($this->getWebsiteId());
                $bDone = true;
                $this->session->unsetData("retry_transfer");
                break;
        }

        // Job as succeeded
        if ($bDone) {
            $this->setStatus(\Reflektion\Catalogexport\Model\Job::STATUS_COMPLETED);
            $this->setEndedAt($this->dateTime->gmtDate());
        }
        $this->save();
    }

    /**
     * Check the cron job configured at the website level and return bool value based on the comparision
     * @return bool | array with key as website Id and corresponding bool value
     */
    public function checkWebsiteCron()
    {
        $schedule = $this->cronSchedule;
        foreach ($this->storeManager->getWebsites() as $cwebsite) {
            $websiteCode = $cwebsite->getCode();
            $websiteId = $cwebsite->getId();
            $expr = $this->scopeConfig->getValue(
                'reflektion_datafeeds/configurable_cron/frequency',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );
            if($expr !== null && trim($expr) !== '') {
                $freqt = preg_split('#\s+#', $expr, null, PREG_SPLIT_NO_EMPTY);
                if(count($freqt) === 5) {
                    $time = $this->systemDateTime->date()->format('Y-m-d H:i:s');
                    $time = strtotime($time);
                    $match[$websiteId] = $schedule->matchCronExpression($freqt[0], strftime('%M', $time))
                        && $schedule->matchCronExpression($freqt[1], strftime('%H', $time))
                        && $schedule->matchCronExpression($freqt[2], strftime('%d', $time))
                        && $schedule->matchCronExpression($freqt[3], strftime('%m', $time))
                        && $schedule->matchCronExpression($freqt[4], strftime('%w', $time));
                }
            }
        }
        return $match;
    }
}
