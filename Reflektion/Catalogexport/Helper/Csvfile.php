<?php
/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    19/07/17
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  CSV files operations - create, open, reopen, close and write
 */
namespace Reflektion\Catalogexport\Helper;

class Csvfile extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $filename;
    private $handle;
    private $path;
    private $errorMessage;
    private $columnHeaders;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        $this->filename = null;
        $this->handle = null;
        $this->path = null;
        $this->errorMessage = null;
        $this->file = $file;
        parent::__construct($context);
    }

    /**
     * Open file
     * @param array $columnHeaders An array of column header names, one for each column
     * @param string $filename fully qualified filename + path. (directory must be writable)
     * @return  boolean
     */
    public function open($filename, array $columnHeaders)
    {
        $this->columnHeaders = $columnHeaders;
        $this->filename = $filename;

        try {
            // Open file
            $this->handle = $this->file->fileOpen($this->filename, 'w');
            // Build header row string
            $rowString = implode(",", $this->encodeFields($columnHeaders)) . "\r\n";
            // Write row to file
            $result = $this->file->fileWrite($this->handle, $rowString);
        } catch (\Exception $e) {
            throwException($e);
            return false;
        }

        return true;
    }

    /**
     * Re Open existing file
     * @param  string $filename fully qualified filename + path. (directory must be writable)
     * @return  boolean
     */
    public function reopen($filename, array $columnHeaders)
    {
        $this->columnHeaders = $columnHeaders;
        $this->filename = $filename;

        try {
            // Reopen file
            $this->handle = $this->file->fileOpen($this->filename, 'a');
        } catch (\Exception $e) {
            throwException($e);
            return false;
        }

        return true;
    }

    /**
     * Close file
     */
    public function close()
    {
        try {
            $this->file->fileClose($this->handle);
        } catch (\Exception $e) {
            throwException($e);
            return false;
        }

        return true;
    }

    /**
     * Write row to file
     *
     * @param  array $rowValues An associative array of columns => values,
    cells for columns not included in this row are left empty
     * @return  boolean
     */
    public function writeRow(array $rowValues)
    {
        try {
            // Filter
            $selectedRowValues = [];
            foreach ($this->columnHeaders as $columnHeader) {
                if (array_key_exists($columnHeader, $rowValues)) {
                    $selectedRowValues[] = $rowValues[$columnHeader];
                } else {
                    $selectedRowValues[] = "";
                }
            }
            // Convert to utf8
            $convertedRowValues = $this->encodeFields($selectedRowValues);
            // Build row string
            $rowString = implode(",", $convertedRowValues) . "\r\n";
            // Write row to file
            $result = $this->file->fileWrite($this->handle, $rowString);
            // Check result
            if ($result != strlen($rowString)) {
                return false;
            }
        } catch (\Exception $e) {
            throwException($e);
            return false;
        }

        return true;
    }

    /**
     * Convert strings in array to Utf8 and encode for CSV file usage
     * @param  array $values
     * @return  array $converted
     */
    private function encodeFields(array $values)
    {
        $converted = [];
        foreach ($values as $value) {
            // Encode in utf8
            $newVal = html_entity_decode($value);
            $newVal = str_replace('"', '""', $newVal);
            // Delimiter
            $newVal = '"' . $newVal . '"';
            $newVal = preg_replace('/[^\x20-\x7E]/', '', $newVal);


            // Converted array
            array_push($converted, $newVal);
        }

        return $converted;
    }

    /**
     * Check whether file
     * @param string $filename fully qualified filename + path.
     * @return  boolean
     */
    public function isFile($filepath)
    {
        return $this->file->isFile($filepath);
    }
}
