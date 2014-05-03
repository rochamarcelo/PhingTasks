<?php
/**
 * This file contains the FtpDownloadTask class
 */
require_once 'phing/Task.php';
/**
 * Download a list of files from the FTP server
 *
 * @package phing.tasks.ftp
 * @author  Marcelo Rocha <contato@omarcelo.com.br>
 */
class FtpDownloadTask extends Task
{
    /**
     * FTP server address
     *
     * @var string
     */
    protected $host;
    /**
     * The port number to connect to
     *
     * @var int
     */
    protected $port = 21;
    /**
     * The username on FTP Server
     *
     * @var string
     */
    protected $username;
    /**
     * The password on FTP Server
     *
     * @var string
     */
    protected $password;
    /**
     * The base dir on FTP Server [Optional]
     *
     * @var string
     */
    protected $dir;
    /**
     * The local base dir [Optional]
     *
     * @var string
     */
    protected $localDir = '';

    /**
     * The transfer mode. Must be either FTP_ASCII or FTP_BINARY.
     */
    protected $mode = FTP_BINARY;
    /**
     * Turns passive mode on or off
     *
     * @var boolean
     */
    protected $passive = false;
    /**
     * Log message level
     *
     * @var string
     */
    protected $logLevel = Project::MSG_VERBOSE;

    /**
     * Any filelists of files that should be appended.
     *
     * @var array
     */
    protected $filelists = array();

    protected $propertyName = 'ftp.download.totalSuccess';

    private $_project;

    /**
     * Total of files downloaded
     *
     * @var integer
     */
    private $_totalSuccess = 0;

    /**
     * "Cache" results of fpt_nlist
     *
     * @var array
     */
    private $_cacheNlist = array();

    /**
     * The main entry point
     *
     * @throws BuildException
     * @access public
     */
    public function main()
    {
        $this->_totalSuccess = 0;
        $this->_project = $this->getProject();

        $connection = ftp_connect($this->host, $this->port);

        if ( !$connection ) {
            throw new BuildException('Could not connect to FTP server '.$this->host.' on port '.$this->port);
        }
        $this->log(
            'Connected to FTP server ' . $this->host . ' on port ' . $this->port,
            $this->logLevel
        );

        //Login on ftp server
        if ( !ftp_login($connection, $this->username, $this->password) ) {
            ftp_close($connection);
            throw new BuildException(
                'Could not login to FTP server ' . $this->host . ' on port ' . $this->port . ' with username ' . $this->username
            );
        }
        $this->log(
            'Logged in to FTP server with username ' . $this->username,
            $this->logLevel
        );
        //Turn on the pssive mode
        if ($this->passive) {
            $this->log('Setting passive mode', $this->logLevel);
            if ( !ftp_pasv($connection, true) ) {
                ftp_close($connection);
                throw new BuildException('Could not set PASSIVE mode');
            }
        }

        // append '/' to the end if necessary
        $dir = substr($this->dir, -1) == '/' ? $this->dir : $this->dir.'/';

        //Change dir
        if ( !ftp_chdir($connection, $dir) ) {
            throw new BuildException('Could not change to directory ' . $dir);
        }
        $this->log('Changed directory ' . $dir, $this->logLevel);

        //Get files
        $this->_cacheNlist = array();
        $this->_downloadFiles($connection);
        $this->project->setProperty($this->getPropertyName(), $this->_totalSuccess);
        //Disconect
        ftp_close($connection);
        $this->log('Disconnected from FTP server', $this->logLevel);
    }

    /**
     * Download files from ftp server
     *
     * @param resource $connection Ftp connection
     *
     * @access private
     * @return self
     */
    private function _downloadFiles($connection)
    {
        // append the files in the filelists
        foreach($this->filelists as $fl) {
            try {
                $this->_downloadFileList($connection, $fl);
            } catch (BuildException $e) {
                $this->log($e->getMessage(), Project::MSG_WARN);
            }
        }
        return $this;
    }

    /**
     * Download files from ftp server defined in a file list
     *
     * @param resource $connection Ftp connection
     * @param FileList $fl         A File list to download
     *
     * @access private
     * @return self
     */
    private function _downloadFileList($connection, $fl)
    {
        $ds = DIRECTORY_SEPARATOR;
        $dir = $fl->getDir($this->project);
        if ( $dir !== null ) {
            $dir = $dir->getPath();
            if ( substr($dir, -1)  != '/' ) {
                $dir = $dir . '/';
            }
            //Change dir
            if ( !ftp_chdir($connection, $dir) ) {
                throw new BuildException('Could not change to directory ' . $dir);
            }
            $this->log('Changed directory ' . $dir, $this->logLevel);
        }
        $files = $fl->getFiles($this->project);

        foreach ($files as $file) {
            if ( !$this->_checkRemoteFileExists($connection, $file) ) {
                $this->log("The file '$file' does not exists", $this->logLevel);
                continue;
            }
            $localFile = $this->localDir . $file;
            $localFile = str_replace(
                array('/', '\\'),
                array($ds, $ds),
                $localFile
            );
            $saveDir = substr($localFile, 0, strrpos($localFile, $ds));
            if ( !empty($saveDir) && !is_dir($saveDir) ) {
                mkdir($saveDir, 0777, true);
            }

            if (!@ftp_get($connection, $localFile, $file, $this->mode)) {
                throw new BuildException("Could not download file '$file' from FTP server");
            } else {
                $this->_totalSuccess++;
                $this->log("Downloaded file $file from FTP server", $this->logLevel);
            }
        }
    }

    /**
     * Check remote file exists
     *
     * @param resource $connection Ftp connection
     * @param string   $file       File path
     *
     * @access private
     * @return self
     */
    private function _checkRemoteFileExists($connection, $file)
    {
        $ds = DIRECTORY_SEPARATOR;
        $origin = ftp_pwd($connection);
        $pos = strrpos($file, $ds);
        $dir = null;
        if ( $pos !== false ) {
            $dir = substr($file, 0, $pos);
            $file = substr($file, $pos+1);
        }

        $list = $this->_getListFilesFTP($connection, $dir);

        if ( empty($list) ) {
            return false;
        }

        return in_array($file, $list, true);
    }

    /**
     * Gets a list of files in the given remote directory
     *
     * @param resource $connection Ftp connection
     * @param string   $dir        Remote directory
     * @param boolean  $useCache   Should use "cached" nlist result[Defaults true]
     *
     * @access private
     * @return array
     */
    private function _getListFilesFTP($connection, $dir, $useCache = true)
    {
        if ( empty($dir) ) {
            $dir = ftp_pwd($connection);
        }
        if ( $useCache === true && isset($this->_cacheNlist[$dir]) ) {
            return $this->_cacheNlist[$dir];
        }

        $origin = ftp_pwd($connection);
        $changed = @ftp_chdir($connection, $dir);
        @ftp_chdir($connection, $origin);
        if ( !$changed ) {
            return array();
        }

        $list = ftp_nlist($connection, $dir);
        if ( is_array($list) ) {
            $this->_cacheNlist[$dir] = $list;
        } else {
            $list = array();
        }
        return $list;
    }

    /**
     * Supports embedded <filelist> element.
     *
     * @return FileList
     */
    public function createFileList() {
        $num = array_push($this->filelists, new FileList());
        return $this->filelists[$num-1];
    }


    /**
     * Gets the FTP server address.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the FTP server address.
     *
     * @param string $host the host
     *
     * @return self
     */
    public function setHost($host)
    {
        $this->host = (string)$host;

        return $this;
    }

    /**
     * Gets the The port number to connect to.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Sets the The port number to connect to.
     *
     * @param int $port the port
     *
     * @return self
     */
    public function setPort($port)
    {
        $this->port = (int)$port;

        return $this;
    }

    /**
     * Gets the The username on FTP Server.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the The username on FTP Server.
     *
     * @param string $username the username
     *
     * @return self
     */
    public function setUsername($username)
    {
        $this->username = (string)$username;

        return $this;
    }

    /**
     * Gets the The password on FTP Server.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Sets the The password on FTP Server.
     *
     * @param string $password the password
     *
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = (string)$password;

        return $this;
    }

    /**
     * Gets the The base dir on FTP Server [Optional].
     *
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Sets the The base dir on FTP Server [Optional].
     *
     * @param string $dir the dir
     *
     * @return self
     */
    public function setDir($dir)
    {
        $this->dir = (string)$dir;

        return $this;
    }

    /**
     * Gets the The local base dir [Optional].
     *
     * @return string
     */
    public function getLocalDir()
    {
        return $this->localDir;
    }

    /**
     * Sets the The local base dir [Optional].
     *
     * @param string $localDir the local dir
     *
     * @return self
     */
    public function setLocalDir($localDir)
    {
        $localDir = (string)$localDir;
        if ( substr($localDir, -1) != DIRECTORY_SEPARATOR ) {
            $localDir .= DIRECTORY_SEPARATOR;
        }
        $this->localDir = $localDir;

        return $this;
    }

    /**
     * Gets the The transfer mode. Must be either FTP_ASCII or FTP_BINARY..
     *
     * @return mixed
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Sets the The transfer mode. Must be either ascii, binary or bin.
     *
     * @param mixed $mode the mode
     *
     * @return self
     */
    public function setMode($mode)
    {
        switch (strtolower($mode)) {
        case 'ascii':
            $this->mode = FTP_ASCII;
            break;
        case 'binary':
        case 'bin':
            $this->mode = FTP_BINARY;
            break;
        default:
            throw new BuildException('The ftp mode must be either ascii, binary or bin.');
        }

        return $this;
    }

    /**
     * Gets the Turns passive mode on or off.
     *
     * @return boolean
     */
    public function getPassive()
    {
        return $this->passive;
    }

    /**
     * Sets the Turns passive mode on or off.
     *
     * @param boolean $passive the passive
     *
     * @return self
     */
    public function setPassive($passive)
    {
        if ( !is_bool($passive) ) {
            throw new BuildException('The ftp passive parameter must be a boolean value.');
        }
        $this->passive = $passive;

        return $this;
    }

    /**
     * Gets the Log message level.
     *
     * @return string
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     * Sets the Log message level.
     *
     * @param string $logLevel the log level
     *
     * @return self
     */
    public function setLogLevel($logLevel)
    {
        switch (strtolower($logLevel)) {
        case "error":
            $this->logLevel = Project::MSG_ERR;
            break;
        case "warning":
            $this->logLevel = Project::MSG_WARN;
            break;
        case "info":
            $this->logLevel = Project::MSG_INFO;
            break;
        case "verbose":
            $this->logLevel = Project::MSG_VERBOSE;
            break;
        case "debug":
            $this->logLevel = Project::MSG_DEBUG;
            break;
        default:
            throw new BuildException('Invalid log level');
        }

        return $this;
    }

    /**
     * Gets the value of propertyName.
     *
     * @return mixed
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Sets the value of propertyName.
     *
     * @param mixed $propertyName the property name
     *
     * @return self
     */
    public function setPropertyName($propertyName)
    {
        $this->propertyName = (string)$propertyName;

        return $this;
    }
}