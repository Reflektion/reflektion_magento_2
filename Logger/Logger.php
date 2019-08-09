<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    10/07/17
 * @time:        12:20 PM
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  To Create Custom Logger
 */
namespace Reflektion\Catalogexport\Logger;

class Logger extends \Monolog\Logger
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/reflektion.log';
}
