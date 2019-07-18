<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    23/11/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To add rfkid 6 dynamically to search form
 */

namespace Reflektion\Catalogexport\Plugin\Search\Helper;
use Magento\Search\Model\QueryFactory;

class Data {

    static $_rfkTag = 0;
    const IVS_Default_Box_Position = 2;
    /**
     * @var \Reflektion\Catalogexport\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \Magento\Framework\App\View
     */
    protected $view;

    public function __construct(
        \Reflektion\Catalogexport\Helper\Data $helper,
        \Magento\Framework\App\View $view
    ) {
        $this->dataHelper = $helper;
        $this->view = $view;
    }

    public function afterGetEscapedQueryText(
        \Magento\Search\Helper\Data $subject,
        $result
    ) {
        $handle = $this->view->getLayout()->getUpdate()->getHandles();
        $ivsFlag = $this->dataHelper->getConfig("reflektion_datafeeds/search/searchflagivs");
        $ivsBoxPosition = $this->dataHelper->getConfig('reflektion_datafeeds/search/searchivsboxindex');
        if ($ivsBoxPosition == "") {
            $ivsBoxPosition = self::IVS_Default_Box_Position;
        }
        $return = '';
        if($ivsFlag == "enabled" && QueryFactory::QUERY_VAR_NAME == "q") {
            if (in_array('catalogsearch_result_index', $handle)) {
                self::$_rfkTag++;
                if (self::$_rfkTag == $ivsBoxPosition) {
                    $return = '" data-rfkid="rfkid_6';
                }
            } else {
                $return = '" data-rfkid="rfkid_6';
            }
        }

        return $result . $return;
    }
}