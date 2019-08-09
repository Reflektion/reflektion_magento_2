<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    10/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Create Custom Handler for logger
 */

namespace Reflektion\Catalogexport\Logger;

use Monolog\Logger as MonoLogger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = MonoLogger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/reflektion.log';
}
