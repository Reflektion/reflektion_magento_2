<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    10/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 */

namespace Reflektion\Catalogexport\Helper;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Number;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_EMAIL_TEMPLATE_FIELD  = 'reflektion_datafeeds/email/job_failure_email';
    /* Here section and group refer to name of section and group where you create this field in configuration*/

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var string
     */
    protected $temp_id;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->_scopeConfig = $context;
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Return store configuration value of your template field that which id you set for template
     *
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $scope, $scopeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            $scope,
            $scopeId
        );
    }

    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Return template id according to store
     *
     * @return mixed
     */
    public function getTemplateId($xmlPath)
    {
        return $this->getConfigValue(
            $xmlPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * [generateTemplate description]  with template file and tempaltes variables values
     * @param  Mixed $emailTemplateVariables
     * @param  Mixed $senderInfo
     * @param  Mixed $receiverInfo
     * @return void
     */
    public function generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $template =  $this->transportBuilder->setTemplateIdentifier($this->temp_id)
            ->setTemplateOptions(
                [
                    /* here you can define area and store of template for which you prepare it */
                    'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,

                    'store' => $this->storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($receiverInfo);
        return $this;
    }

    /**
     * [send email on job failure]
     * @param  String $msg
     * @param  Number $websiteId
     * @param  Number $jobId
     * @return void
     */
    /* your send mail method*/
    public function emailError($msg, $websiteId, $jobId)
    {
        $emails = $this->getConfigValue(
            "reflektion_datafeeds/email/list",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getStoreId()
        );
        if (!empty($emails)) {
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();

            /* Receiver Detail  */
            $receiverInfo = explode(",", $emails);

            $genName = $this->getConfigValue(
                "trans_email/ident_general/name",
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );
            $genEmail = $this->getConfigValue(
                "trans_email/ident_general/email",
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );
            /* Sender Detail  */
            $senderInfo = [
                'name' => $genName,
                'email' => $genEmail,
            ];
            /* Assign values to template variables  */
            $emailTemplateVariables = [];
            $emailTemplateVariables["websiteId"] = $websiteId;
            $emailTemplateVariables["jobId"] = $jobId;
            $emailTemplateVariables["msg"] = $msg;

            $this->temp_id = $this->getTemplateId(self::XML_PATH_EMAIL_TEMPLATE_FIELD);
            $this->inlineTranslation->suspend();
            $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        }
    }
}
