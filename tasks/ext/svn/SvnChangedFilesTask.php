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
        $toDir = trim($this->getToDir());
        if ( empty($toDir) ) {
            $toDir = 'SvnChangedFiles';
        }
        $toDir .= DIRECTORY_SEPARATOR;

        $this->setup('diff');
        $repositoryPath = $this->getRepositoryUrl();
        if ( empty($repositoryPath) ) {
            $repositoryPath = $this->getWorkingCopy();
        }
        $repositoryPath = rtrim($repositoryPath, '//');

        $message = "Finding changed files at SVN repository '";
        $message .= $repositoryPath . "' (range: {$this->revisionRange})";
        $this->log($message);

        // revision range
        $switches = array(
            'r' => $this->revisionRange,
            'summarize' => true
        );

        $output = $this->run(array(), $switches);
        $this->parseOutput($output);

        if ( empty($output['path']) ) {
            return;
        }

        $changed = $deleted = array();
        $this->log('Copying files to ' . $toDir);
        if ( !file_exists($toDir) && !mkdir($toDir, 0666, true) ) {
            throw new BuildException("Failed to create the directory $toDir");
        }

        $txtChanged = fopen($toDir . 'changed.txt', 'w');

        if ( !$txtChanged ) {
            throw new BuildException("Failed to open '$toDir.changed.txt'");
        }

        $txtDeleted = fopen($toDir . 'deleted.txt', 'w');
        if ( !$txtDeleted ) {
            throw new BuildException("Failed to open '$toDir.deleted.txt'");
        }

        $txtAll = fopen($toDir . 'all.txt', 'w');
        if ( !$txtAll ) {
            throw new BuildException("Failed to open '$toDir.all.txt'");
        }

        foreach ( $output['path'] as $path ) {
            $file = (string)$path['text'];
            $original = $file;
            if ( $this->forceRelativePath ) {
                $file = str_replace($repositoryPath, '', $file);
                $file  = ltrim($file, '/ \\');
            }

            if ( (string)$path["item"] != 'deleted' ) {
                $changed[] = $file;
                $this->copy($original, $toDir . 'changed/' . $file);
                fwrite($txtChanged, $file . "\n");
            } else {
                fwrite($txtDeleted, $file . "\n");
                $deleted[] = $file;
            }
            fwrite($txtAll, $file . "\n");
        }
        fclose($txtChanged);
        fclose($txtDeleted);
        fclose($txtAll);
        $changed = implode(',', $changed);
        $deleted = implode(',', $deleted);
        $this->project->setProperty($this->getPropertyNameChanged(), $changed);
        $this->project->setProperty($this->getPropertyNameDeleted(), $deleted);
    }

    /**
     * Parse output to array
     *
     * @param array &$output Svn diff output
     *
     * @return null
     */
    protected function parseOutput(&$output)
    {
        if ( is_array($output) ) {
            if ( !array_key_exists('path', $output) ) {
                $output['path'] = array();
            }
            return;
        }

        if ( !is_string($output) ) {
            return;
        }
        $xml = @simplexml_load_string($output);
        $output = array('path' => array());
        if ( !isset($xml->paths->path) ) {
            return;
        }

        foreach ( $xml->paths->path as $path ) {
            $attr = $path->attributes();
            $output['path'][] = array(
                'text' => (string)$path,
                'props' => (string)$attr->props,
                'item' => (string)$attr->item,
                'kind' => (string)$attr->kind
            );
        }
    }

    /**
     * Copy a file / dir
     *
     * @param string $source Path to the source file.
     * @param string $dest   The destination path
     *
     * @return null
     */
    protected function copy($source, $dest)
    {
        $dir = dirname($dest);
        if ( !file_exists($dir) && !mkdir($dir, 0666, true) ) {
            throw new BuildException("Failed to create the directory $dir");
        }

        //dir
        if ( is_dir($source) ) {
            if ( !file_exists($dest) && !mkdir($dest, 0666, true) ) {
                throw new BuildException("Failed to create the directory $dest");
            }
            return;
        }
        //file
        if ( !copy($source, $dest) ) {
            throw new BuildException("Failed to copy the file $source to $dest");
        }
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