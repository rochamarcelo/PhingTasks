<?php
/**
 * This file contains the SvnChangedFilesTask class
 */
require_once 'phing/Task.php';
require_once 'phing/tasks/ext/svn/SvnBaseTask.php';
/**
 * Get a list of file changed in a range of revisions
 *
 * @package phing.tasks.ext.svn
 * @author  Marcelo Rocha <contato@omarcelo.com.br>
 */
class SvnChangedFilesTask extends SvnBaseTask
{
    /**
     * The revision range used to compare the files (e.g., 1:HEAD, 1:10, 50:1000)
     *
     * @var string
     */
    protected $revisionRange;

    /**
     *
     * @var string
     */
    protected $forceRelativePath = false;

    /**
     *
     * @var string
     */
    protected $propertyName = "ftp.changedfiles";

    /**
     * The main entry point
     *
     * @throws BuildException
     * @access public
     */
    function main()
    {
        $this->setup('diff');
        $repositoryPath = $this->getRepositoryUrl();
        if ( empty($repositoryPath) ) {
            $repositoryPath = $this->getWorkingCopy();
        }

        $message = "Finding changed files at SVN repository '";
        $message .= $repositoryPath . "' (range: {$this->revisionRange})";
        $this->log($message);

        // revision range
        $switches = array(
            'r' => $this->revisionRange,
            'summarize' => true,
        );

        $output = $this->run(array(), $switches);
        $xml = @simplexml_load_string($output);

        if ( !$xml || !isset($xml->paths) ) {
            throw new BuildException("Failed to parse the output of 'svn diff --xml'.");
        }

        if ( !isset($xml->paths->path) ) {
            return;
        }
        $list = array();
        foreach ( $xml->paths->path as $path ) {
            if ( (string)$path["item"] == 'deleted' ) {
                continue;
            }
            $file = (string)$path;

            if ( $this->forceRelativePath ) {
                $file = str_replace($repositoryPath, '', $file);
            }

            $list[] = $file;
        }
        $list = implode(',', $list);
        $this->project->setProperty($this->getPropertyName(), $list);
    }

    /**
     * Set the revision range used to compare the files (e.g., 1:HEAD, 1:10, 50:1000)
     *
     * @param string $revisionRange range used to compare the files
     *
     * @access public
     */
    public function setRevisionRange($revisionRange)
    {
        if ( !preg_match('/^[0-9]*\:([0-9]*|HEAD)$/', $revisionRange) ) {
            throw new BuildException("You must specify a valid revision range!");
        }

        $this->revisionRange = $revisionRange;
    }

    /**
     * Set the forceRelativePath, define if must force to return files with relative path
     *
     * @param boolean $forceRelativePath The boolean value
     *
     * @access public
     */
    public function setForceRelativePath($forceRelativePath)
    {
        if ( !is_bool($forceRelativePath) ) {
            throw new BuildException("You must specify a boolean value for 'forceRelativePath'!");
        }
        $this->forceRelativePath = $forceRelativePath;
    }

    /**
     * Sets the name of the property to set the list of files changed
     *
     * @param string $propertyName The property name
     *
     * @access public
     */
    public function setPropertyName($propertyName)
    {
        $this->propertyName = (string)$propertyName;
    }

    /**
     * Get the name of the property used to set a list of files changed
     *
     * @access public
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }
}