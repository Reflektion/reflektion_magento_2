<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    11/09/17
 * @license      https://opensource.org/licenses/OSL-3.0
 */

namespace Reflektion\Catalogexport\Cron;

class ExportFeedCron
{
    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Reflektion\Catalogexport\Model\Job
     */
    protected $job;

    /**
     * ExportFeedCron constructor.
     * @param \Reflektion\Catalogexport\Logger\Logger $logger
     * @param \Reflektion\Catalogexport\Model\Job $job
     */

    public function __construct(
        \Reflektion\Catalogexport\Logger\Logger $logger,
        \Reflektion\Catalogexport\Model\Job $job
    ) {
        $this->logger = $logger;
        $this->job = $job;
    }

    /**
     * Generate and send new datafeed files
     */
    public function processDailyFeeds()
    {
        $this->logger->info(
            "**********************************************************"
        );
        $this->logger->info(
            "Data feeds cron process started..."
        );
        $this->logger->info('Memory usage: ' . memory_get_usage());
        $this->logger->info(
            "**********************************************************"
        );
        $this->logger->info('Memory usage: ' . memory_get_usage());

        // schedule daily feed jobs for all websites
        $this->scheduleJobs();

        // Log mem usage
        $this->logger->info('Memory usage: ' . memory_get_usage());
        $collectionJobs = $this->job->getCollection()
                            ->addFieldToFilter(
                                'status',
                                ["eq"=>\Reflektion\Catalogexport\Model\Job::STATUS_SCHEDULED]
                            );
        $countScheduled = $collectionJobs->getSize();
        for ($k = 0; $k < $countScheduled; $k++) {
            $this->runJob(); //products
            $this->runJob(); //categories
            $this->runJob(); //sales
            $this->runJob(); //file transfer
        }
    }

    /**
     * Schedule any daily feed jobs which are necessary when we hit the daily trigger time
     */
    protected function scheduleJobs()
    {
        try {
            \Reflektion\Catalogexport\Model\Job::scheduleAllDailyJobs();
        } catch (\Exception $e) {
            $this->logger->info('Failed to schedule daily jobs, error:');
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * Grab the next job and run it, if it exists
     */
    protected function runJob()
    {
        try {
            $job = \Reflektion\Catalogexport\Model\Job::getNextJobFromQueue();
            if ($job !== false) {
                $job->run();
            }
        } catch (\Exception $e) {
            $this->logger->info("Failed to run job, error:");
            $this->logger->info($e->getMessage());
        }
    }
}
