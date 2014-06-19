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
     * the property name to store the changed(modified or added) files
     *
     * @var string
     */
    protected $propertyNameChanged = "svn.changed";

    /**
     * the property name to store the deleted files
     *
     * @var string
     */
    protected $propertyNameDeleted = 'svn.deleted';

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
        $changed = $deleted = array();
        foreach ( $xml->paths->path as $path ) {
            $file = (string)$path;

            if ( $this->forceRelativePath ) {
                $file = str_replace($repositoryPath, '', $file);
            }
            if ( (string)$path["item"] != 'deleted' ) {
                $changed[] = $file;
            } else {
                $deleted[] = $file;
            }
        }

        $changed = implode(',', $changed);
        $deleted = implode(',', $deleted);
        $this->project->setProperty($this->getPropertyNameChanged(), $changed);
        $this->project->setProperty($this->getPropertyNameDeleted(), $deleted);
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
     * Sets the the property name to store the changed(modified or added) files.
     *
     * @param string $propertyNameChanged the property name changed
     *
     * @return self
     */
    public function setPropertyNameChanged($propertyNameChanged)
    {
        $this->propertyNameChanged = (string)$propertyNameChanged;

        return $this;
    }

    /**
     * Gets the the property name to store the changed(modified or added) files.
     *
     * @return string
     */
    public function getPropertyNameChanged()
    {
        return $this->propertyNameChanged;
    }

    /**
     * Sets the the property name to store the deleted files.
     *
     * @param string $propertyNameDeleted the property name deleted
     *
     * @return self
     */
    public function setPropertyNameDeleted($propertyNameDeleted)
    {
        $this->propertyNameDeleted = $propertyNameDeleted;

        return $this;
    }

    /**
     * Gets the the property name to store the deleted files.
     *
     * @return string
     */
    public function getPropertyNameDeleted()
    {
        return $this->propertyNameDeleted;
    }
}