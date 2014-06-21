<?php
/**
 * This file contains the JenkinsGetRevisionNumberTask class
 */
require_once 'phing/Task.php';
/**
 * Get the revision number of a jenkins revision.txt file
 *
 * @package phing.tasks.ext.svn
 * @author  Marcelo Rocha <contato@omarcelo.com.br>
 */
class JenkinsGetRevisionNumberTask extends Task
{
    /**
     * file path
     *
     * @var string
     */
    protected $file;

    /**
     * property name to store the revision number
     *
     * @var string
     */
    protected $propertyName = "jenkins.revisionNumber";

    /**
     * The main entry point
     *
     * @throws BuildException
     * @access public
     */
    function main()
    {
        if ( empty($this->file) ) {
            throw new BuildException("The file path is required");
        }

        if ( !file_exists($this->file) || !is_file($this->file) ) {
            throw new BuildException("The file '{$this->file}' does not exists");
        }

        $handle = fopen($this->file, 'r');
        $line = fgets($handle);
        fclose($handle);

        $pos = strrpos($line, '/');
        if ( $pos === false ) {
            throw new BuildException("Could not extract the revision number");
        }
        $revision = (int)substr($line, $pos +1);
        if ( empty($revision) ) {
            throw new BuildException("Could not extract the revision number");
        }
        $this->project->setProperty($this->getPropertyName(), $revision);
    }

    /**
     * Gets the file path.
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sets the file path.
     *
     * @param string $file the file
     *
     * @return self
     */
    public function setFile($file)
    {
        $this->file = (string)$file;

        return $this;
    }

    /**
     * Gets the property name to store the revision number.
     *
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Sets the property name to store the revision number.
     *
     * @param string $propertyName the property name
     *
     * @return self
     */
    public function setPropertyName($propertyName)
    {
        $this->propertyName = (string)$propertyName;

        return $this;
    }
}