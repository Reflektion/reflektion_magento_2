<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    27/06/17
 * @time:        11:57 AM
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To add comment in system config fields
 */
namespace Reflektion\Catalogexport\Block\Adminhtml\System\Config;

class CommentText extends \Magento\Config\Block\System\Config\Form\Field
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $element->getComment(); // Get only comment
    }
}
