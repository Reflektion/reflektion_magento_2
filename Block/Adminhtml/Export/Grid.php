<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    12/07/17
 * @time:        1:56 PM
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Preparing collection dynamically and scheduling job
 */

namespace Reflektion\Catalogexport\Block\Adminhtml\Export;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected function _prepareCollection()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->create("Reflektion\Catalogexport\Model\ResourceModel\GenerateFeeds\Collection");
        $websites = $this->_storeManager->getWebsites();
        foreach ($websites as $website) {
            $websiteId = $website->getId();
            $websiteCode = $website->getCode();
            if ($this->_scopeConfig->getValue(
                'reflektion_datafeeds/general/allfeedsenabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            ) == 'enabled') {
                $feedTypes = '';
                foreach (\Reflektion\Catalogexport\Model\Generatefeeds::getFeedTypes() as $curFeedType) {
                    if ($this->_scopeConfig->getValue(
                        'reflektion_datafeeds/feedsenabled/' . $curFeedType,
                        \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                        $websiteCode
                    ) == 'enabled') {
                        if (!empty(trim($feedTypes))) {
                            $feedTypes .= ', ';
                        }
                        $feedTypes .= $curFeedType;
                    }
                }
                $sftpUser = $this->_scopeConfig->getValue(
                    'reflektion_datafeeds/connect/username',
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                    $websiteCode
                );
                $sftpDestination = $this->_scopeConfig->getValue(
                    'reflektion_datafeeds/connect/hostname',
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                    $websiteCode
                );
                $newItem = $collection->getNewEmptyItem();
                $collection->addItem(
                    $newItem->setData(
                        [
                            'id' => $websiteId,
                            'website_name' => $website->getName(),
                            'website_code' => $website->getCode(),
                            'feeds' => $feedTypes,
                            'sftp_destination' => $sftpDestination,
                            'sftp_user' => $sftpUser,
                        ]
                    )
                );
            }
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header' => __('Website ID'),
            'index' => 'id',
        ]);
        $this->addColumn('website_name', [
            'header' => __('Website Name'),
            'index' => 'website_name',
        ]);
        $this->addColumn('website_code', [
            'header' => __('Website Code'),
            'index' => 'website_code',
        ]);
        $this->addColumn('feeds', [
            'header' => __('Feeds to Send'),
            'index' => 'feeds',
        ]);
        $this->addColumn('sftp_destination', [
            'header' => __('SFTP Destination'),
            'index' => 'sftp_destination',
        ]);
        $this->addColumn('sftp_user', [
            'header' => __('SFTP User'),
            'index' => 'sftp_user',
        ]);
        $this->addColumn('action', [
            'header' => __('Action'),
            'filter' => false,
            'sortable' => false,
            'renderer' => 'Reflektion\Catalogexport\Block\Adminhtml\Export\Grid\Renderer\Action'
        ]);

        return parent::_prepareColumns();
    }
    /**
     * Add column filtering conditions to collection
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        return $this;
    }
}
