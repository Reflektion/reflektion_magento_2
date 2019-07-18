<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    01/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Download last feed out
 */

namespace Reflektion\Catalogexport\Block\Adminhtml\Job\Grid\Renderer;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
{
    /**
     * @var \Reflektion\Catalogexport\Model\Job
     */
    protected $job;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Reflektion\Catalogexport\Model\Job
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Reflektion\Catalogexport\Model\Job $job,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $data);
        $this->job = $job;
    }
    /**
     * Renders column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $feedType = (empty($row->getFeedType()) ? true : $row->getFeedType());
        $websiteId = $row->getwebsiteId();
        $jobId = $this->getLastFeedId($feedType, $websiteId);
        if ($jobId == $row->getId()) {
            $this->getColumn()->setActions([[
                'url' => $this->getUrl(
                    '*/*/downloadfeed',
                    [
                        'website_id' => $websiteId,
                        'feed_type' => $row->getFeedType()
                    ]
                ),
                'caption' => __('download'),
            ]]);
        } else {
            $this->getColumn()->setActions([[
                'url' => '',
                'caption' => __(''),
            ]]);
        }

        return parent::render($row);
    }

    protected function getLastFeedId($feedType, $websiteId)
    {
        $newJob = $this->job->getCollection()
            ->addFilter('feed_type', $feedType)
            ->addFilter('website_id', $websiteId)
            ->addFilter('status', \Reflektion\Catalogexport\Model\Job::STATUS_COMPLETED)
            ->setOrder('ended_at', 'ASC')
            ->getLastItem();

        return $newJob->getId();
    }
}
