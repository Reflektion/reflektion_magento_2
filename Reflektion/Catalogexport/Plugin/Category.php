<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    23/11/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To modify the category url dynamically
 */

namespace Reflektion\Catalogexport\Plugin;

class Category
{
    const CATEGORY_RFK_1 = 1;
    const CATEGORY_RFK_URI = 2;
    const CATEGORY_RFK_SUBDOMAIN = 3;
    /**
     * @param \Reflektion\Catalogexport\Helper\Analytics
     */
    protected $analyticsHelper;

    public function __construct(
        \Reflektion\Catalogexport\Helper\Analytics $analyticsHelper
    ) {
        $this->analyticsHelper = $analyticsHelper;
    }

    public function afterGetUrl(
        \Magento\Catalog\Model\Category $subject,
        $result
    ) {
        $catEnabled = $this->analyticsHelper->getConfig("reflektion_datafeeds/categorypages/fpsenabled");
        if ($catEnabled == 'enabled') {
            $option = $this->analyticsHelper->getConfig('reflektion_datafeeds/categorypages/fpsoption');
            $subDText = $this->analyticsHelper->getConfig('reflektion_datafeeds/categorypages/subdomaintext');
            $uriText = $this->analyticsHelper->getConfig('reflektion_datafeeds/categorypages/uritext');
            if ($option == self::CATEGORY_RFK_SUBDOMAIN) {
                if ($subDText != "") {
                    $return = $subDText . $subject->getRequestPath();
                    return $return;
                }
            } elseif ($option == self::CATEGORY_RFK_URI) {
                if ($uriText != "") {
                    $return = $uriText . '/' . $subject->getRequestPath();
                    return $subject->getUrlInstance()->getDirectUrl($return);
                }

            } elseif ($option == self::CATEGORY_RFK_1) {
                $rfk = '?rfk=1';
                return $subject->getUrlInstance()->getDirectUrl($subject->getRequestPath()) . $rfk;
            }
        }

        return $result;
    }
}
