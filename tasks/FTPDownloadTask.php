<?php
/**
 * This file contains the FtpDownloadTask class
 */
require_once 'phing/Task.php';
/**
 * Download a list of files from the FTP server
 *
 * @package phing.tasks.ext.ftp
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
    protected $remotedir;
    /**
     * The local base dir [Optional]
     *
     * @var string
     */
    protected $localDir;

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
     * The main entry point
     *
     * @throws BuildException
     * @access public
     */
    public function main()
    {
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
    public function getRemotedir()
    {
        return $this->remotedir;
    }

    /**
     * Sets the The base dir on FTP Server [Optional].
     *
     * @param string $remotedir the remotedir
     *
     * @return self
     */
    public function setRemotedir($remotedir)
    {
        $this->remotedir = (string)$remotedir;

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
        $this->localDir = (string)$localDir;

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
        switch (strtolower($level)) {
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
}