<?php
/**
 * This file contains the DeployLogTask class
 */
require_once 'phing/Task.php';
require_once 'PHPExcel/PHPExcel/IOFactory.php';
/**
 * Log using XLS
 *
 * @package phing.tasks.ftp
 * @author  Marcelo Rocha <contato@omarcelo.com.br>
 */
class DeployLogTask extends Task
{
    /**
     * XLS file path
     *
     * @var string
     */
    protected $file;

    /**
     * Log date of the action
     *
     * @var string
     */
    protected $date = '';
    /**
     * Log time of the action
     *
     * @var string
     */
    protected $time = '';
    /**
     * Responsible for the action
     *
     * @var string
     */
    protected $responsible = '';

    /**
     * Client name
     *
     * @var string
     */
    protected $client;

    /**
     * Job of the action
     *
     * @var string
     */
    protected $job = '';
    /**
     * SVN revisions
     *
     * @var string
     */
    protected $revision = '';
    /**
     * Action comment
     *
     * @var string
     */
    protected $comment = '';

    /**
     * The main entry point
     *
     * @throws BuildException
     * @access public
     */
    public function main()
    {
        if ( empty($this->file) || !file_exists($this->file) || is_dir($this->file) ) {
            throw new BuildException("The file '{$this->file}' does not exists");
        }
        $this->_verifyProperties();
        try {
            $objPHPExcel = PHPExcel_IOFactory::load($this->file);

        } catch ( Exception $e) {
            throw new BuildException("Could not open file '{$this->file}'");
        }
        $this->log("Loaded file '{$this->file}'", Project::MSG_VERBOSE);

        $sheet = $objPHPExcel->getActiveSheet();
        $row = $sheet->getHighestRow();
        $row++;
        $sheet->setCellValue('A' . $row, $this->date);
        $sheet->setCellValue('B' . $row, $this->time);
        $sheet->setCellValue('C' . $row, $this->responsible);
        $sheet->setCellValue('D' . $row, $this->client);
        $sheet->setCellValue('E' . $row, $this->job);
        $sheet->setCellValue('F' . $row, $this->revision);
        $sheet->setCellValue('G' . $row, $this->comment);

        $writerType = $this->_getWriterType();
        //Save file
        try {
            $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, $writerType);
            $tmp = tempnam(sys_get_temp_dir(), 'xls_log');
            $writer->save($tmp);
            $this->log("Saved temporary file '$tmp'", Project::MSG_VERBOSE);
            $renamed = copy($tmp, $this->file);
            if ( !$renamed ) {
                throw new Exception;
            }
            $this->log("Saved file '{$this->file}'", Project::MSG_VERBOSE);
        } catch (Exception $e) {
            unset($writer);
            throw new BuildException("Could not save file '{$this->file}'");
        }
        unset($writer);
    }

    /**
     * Verify the properties
     *
     * @throws BuildException If [any required property is empty]
     * @return null
     */
    private function _verifyProperties()
    {
        $required = array(
            'date', 'time', 'responsible', 'client', 'job', 'comment'
        );
        foreach ( $required as $property ) {
            if ( trim($this->$property) == '' ) {
                throw new BuildException("Property $property is required");
            }
        }
    }

    /**
     * Get the writer type based on the file extension
     *
     * @throws BuildException If could not indentify the writer type
     * @return string
     */
    private function _getWriterType()
    {
        $pos = strrpos($this->file, '.');
        if ( !$pos > 0 ) {
            return 'Excel2007';
        }
        $pos++;
        $ext = substr($this->file, $pos);

        if ( empty($ext) ) {
            return 'Excel2007';
        }
        switch (strtolower($ext)) {
            case 'xlsx':            //  Excel (OfficeOpenXML) Spreadsheet
            case 'xlsm':            //  Excel (OfficeOpenXML) Macro Spreadsheet (macros will be discarded)
            case 'xltx':            //  Excel (OfficeOpenXML) Template
            case 'xltm':            //  Excel (OfficeOpenXML) Macro Template (macros will be discarded)
                return 'Excel2007';
            case 'xls':             //  Excel (BIFF) Spreadsheet
            case 'xlt':             //  Excel (BIFF) Template
                return 'Excel5';
            case 'ods':             //  Open/Libre Offic Calc
            case 'ots':             //  Open/Libre Offic Calc Template
                return 'OOCalc';
            case 'slk':
                return 'SYLK';
            case 'xml':             //  Excel 2003 SpreadSheetML
                return 'Excel2003XML';
            case 'gnumeric':
                return 'Gnumeric';
            case 'htm':
            case 'html':
                return 'HTML';
            case 'csv':
                return 'CSV';
            default:
                throw new BuildException("Could not identify the writer for file '{$this->file}'");
        }

    }

    /**
     * Gets the value of file.
     *
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sets the value of file.
     *
     * @param mixed $file the file
     *
     * @return self
     */
    public function setFile($file)
    {
        $this->file = trim($file);

        return $this;
    }

    /**
     * Gets the Log date of the action.
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Sets the Log date of the action.
     *
     * @param string $date the date
     *
     * @return self
     */
    public function setDate($date)
    {
        $this->date = (string)$date;

        return $this;
    }

    /**
     * Gets the Log time of the action.
     *
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Sets the Log time of the action.
     *
     * @param string $time the time
     *
     * @return self
     */
    public function setTime($time)
    {
        $this->time = (string)$time;

        return $this;
    }

    /**
     * Gets the Responsible for the action.
     *
     * @return string
     */
    public function getResponsible()
    {
        return $this->responsible;
    }

    /**
     * Sets the Responsible for the action.
     *
     * @param string $responsible the responsible
     *
     * @return self
     */
    public function setResponsible($responsible)
    {
        $this->responsible = (string)$responsible;

        return $this;
    }

    /**
     * Gets the Client name.
     *
     * @return string
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the Client name.
     *
     * @param string $client the client
     *
     * @return self
     */
    public function setClient($client)
    {
        $this->client = (string)$client;

        return $this;
    }

    /**
     * Gets the Job of the action.
     *
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Sets the Job of the action.
     *
     * @param string $job the job
     *
     * @return self
     */
    public function setJob($job)
    {
        $this->job = (string)$job;

        return $this;
    }

    /**
     * Gets the SVN revisions.
     *
     * @return string
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Sets the SVN revisions.
     *
     * @param string $revision the revision
     *
     * @return self
     */
    public function setRevision($revision)
    {
        $this->revision = (string)$revision;

        return $this;
    }

    /**
     * Gets the Action comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Sets the Action comment.
     *
     * @param string $comment the comment
     *
     * @return self
     */
    public function setComment($comment)
    {
        $this->comment = (string)$comment;

        return $this;
    }
}