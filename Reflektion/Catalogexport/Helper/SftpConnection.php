<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Helper to transfer files through SFTP
 */

namespace Reflektion\Catalogexport\Helper;

use Reflektion\Catalogexport\Logger\Logger;
use phpseclib\Net\SFTP;

class SftpConnection extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SFTP_TIMEOUT = 20;
    private $oConnection = null;
    /**
     * @var \Reflektion\Catalogexport\Logger\Logger
     */
    protected $logger;
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Reflektion\Catalogexport\Logger\Logger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Logger $logger
    ) {
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Connect
     * @return  boolean
     */
    public function connect($host, $port, $user, $pw)
    {

        // Check credentials
        if (empty(trim($host))) {
            throw new \Exception('Invalid SFTP host: ' . $host);
        }
        if (empty(trim($port)) || !ctype_digit($port)) {
            throw new \Exception('Invalid SFTP port: ' . $port);
        }
        if (empty(trim($user))) {
            throw new \Exception('Invalid SFTP user: ' . $user);
        }
        if (empty(trim($pw))) {
            throw new \Exception('Invalid SFTP password: ' . $pw);
        }
        try {
            $this->oConnection = new \phpseclib\Net\SFTP($host, $port, self::SFTP_TIMEOUT);
            if (!$this->oConnection->login($user, $pw)) {
                throw new \Exception("Unable to open SFTP connection as %s@%s %s", $user, $host, $pw);
            }
            return true;
        } catch (\Exception $e) {
            throwException($e);
            $this->logger->info($e->getMessage());
            $this->logger->info("SFTP reported error is " . $e);
        }
        return false;
    }

    /**
     * Close
     * @return  boolean
     */
    public function close()
    {
        try {
            // Close connection
            if (isset($this->oConnection)) {
                $bRes = $this->oConnection->disconnect();
                unset($this->oConnection);
                return $bRes;
            } else {
                throw new \Exception('Connection not open!');
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        return false;
    }

    /**
     * Is connected
     * @return  boolean
     */
    public function isConnected()
    {
        return (isset($this->oConnection));
    }

    /**
     * Change directory
     * @param string directory
     * @return boolean
     */
    public function changeDir($sDir)
    {
        try {
            if (!$this->isConnected()) {
                return false;
            }
            return $this->oConnection->chdir($sDir);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        return false;
    }

    /**
     * Transfer file
     * @param  string Local file path
     * @return  boolean
     */
    public function putFile($sLocalFilePath)
    {
        $this->_logger->debug("local file path putFile");
        $this->_logger->debug($sLocalFilePath);
        try {
            // Close connection
            if (!$this->isConnected()) {
                return false;
            }
            $sFilename = basename($sLocalFilePath); // Get filename
            // Transfer
            $bSuccess = $this->oConnection->put($sFilename, $sLocalFilePath, SFTP::SOURCE_LOCAL_FILE);
            if (!$bSuccess) {
                $this->logger->info('SFTP Error: ' . $this->oConnection->getLastSFTPError());
            }
            return $bSuccess;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        return false;
    }

    /**
     * Transfer file and delete when successful as one atomic operation
     * @param  string Local file path
     * @return  boolean
     */
    public function putFeedFile($sLocalFilePath)
    {
        $this->_logger->debug("local file path putFeedFile");
        $this->_logger->debug($sLocalFilePath);
        try {
            $bSuccess = $this->putFile($sLocalFilePath);
            return $bSuccess;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        return false;
    }
}
