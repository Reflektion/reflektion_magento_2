<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    28/08/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To load init.js on top in head section
 */
namespace Reflektion\Catalogexport\Model\Rewrite\Result;

class Page extends \Magento\Framework\View\Result\Page
{
    /**
     * @var \\Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\View\Layout\ReaderPool $layoutReaderPool
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\View\Layout\BuilderFactory $layoutBuilderFactory
     * @param \Magento\Framework\View\Layout\GeneratorPool $generatorPool
     * @param \Magento\Framework\View\Page\Config\RendererFactory $pageConfigRendererFactory
     * @param \Magento\Framework\View\Page\Layout\Reader $pageLayoutReader
     * @param string $template
     * @param bool $isIsolated
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\View\Layout\ReaderPool $layoutReaderPool,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Layout\BuilderFactory $layoutBuilderFactory,
        \Magento\Framework\View\Layout\GeneratorPool $generatorPool,
        \Magento\Framework\View\Page\Config\RendererFactory $pageConfigRendererFactory,
        \Magento\Framework\View\Page\Layout\Reader $pageLayoutReader,
        $template,
        $isIsolated = false
    ) {

        parent::__construct(
            $context,
            $layoutFactory,
            $layoutReaderPool,
            $translateInline,
            $layoutBuilderFactory,
            $generatorPool,
            $pageConfigRendererFactory,
            $pageLayoutReader,
            $template,
            $isIsolated
        );
        $this->storeManager = $context->getStoreManager();
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * Assign variable
     *
     * @param   string|array $key
     * @param   mixed $value
     * @return  $this
     */
    protected function assign($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $subKey => $subValue) {
                if($subKey == 'requireJs') {
                    $html = $this->getRfkScript();
                    if ($html != '') {
                        $subValue = $html. "\n". $subValue;
                    }
                }
                $this->assign($subKey, $subValue);
            }
        } else {
            $this->viewVars[$key] = $value;
        }
        return $this;
    }

    /*
     * Adding RFK script to HEAD
     */

    public function getRfkScript()
    {
        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $yesToAdd = $this->scopeConfig->getValue(
            'reflektion_datafeeds/general/addbycustomerid',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        if ($yesToAdd == 'disabled') {
            return '';
        }
        $customerInitUrl = $this->scopeConfig->getValue(
            'reflektion_datafeeds/search/rfkjs',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        $html = '<script type="text/javascript" src="'.
            $customerInitUrl .
            '" async="true"></script>';
        return $html;
    }
}
