<?php
/**
 * This file contains the ExtractPHPUnitResultTask class
 */
require_once 'phing/Task.php';
/**
 * Phing tast to extrat some final numbers of a PHPUnit result (tests, errors, failures, assertions)
 *
 * @package phing.tasks.ext.svn
 * @author  Marcelo Rocha <contato@omarcelo.com.br>
 */
class ExtractPHPUnitResultTask extends Task
{
    /**
     * file path
     *
     * @var string
     */
    protected $file;

    /**
     * property name to store the number of tests
     *
     * @var string
     */
    protected $propertyTests = 'phpunit.tests';

    /**
     * property name to store the number of assertions
     *
     * @var string
     */
    protected $propertyAssertions = 'phpunit.assertions';

    /**
     * property name to store the number of failures
     *
     * @var string
     */
    protected $propertyFailures = 'phpunit.failures';

    /**
     * property name to store the number of errors
     *
     * @var string
     */
    protected $propertyErrors = 'phpunit.errors';

    /**
     * property name to store the verification of success
     *
     * @var string
     */
    protected $propertySuccess = 'phpunit.success';

    /**
     * The main entry point
     *
     * @throws BuildException
     * @access public
     */
    function main()
    {
        $this->log('Checking phpunit report');
        if ( empty($this->file) ) {
            throw new BuildException("The file path is required");
        }

        if ( !file_exists($this->file) || !is_file($this->file) ) {
            throw new BuildException("The file '{$this->file}' does not exists");
        }

        $xml = @simplexml_load_file($this->file);
        $attrs = $xml->testsuite->attributes();

        if ( !$xml || !isset($xml->testsuite) ) {
            throw new BuildException("Failed to parse the phpunit file '{$this->file}'");
        }
        $attrs = $xml->testsuite->attributes();

        if ( !isset($attrs->errors) || !isset($attrs->failures) ) {
            throw new BuildException("Could not extract phpunit result");
        }

        if ( !isset($attrs->tests) || !isset($attrs->assertions) ) {
            throw new BuildException("Could not extract phpunit result");
        }

        $this->project->setProperty($this->getPropertyTests(), (int)$attrs->tests);
        $this->project->setProperty($this->getPropertyAssertions(), (int)$attrs->assertions);
        $this->project->setProperty($this->getPropertyFailures(), (int)$attrs->failures);
        $this->project->setProperty($this->getPropertyErrors(), (int)$attrs->errors);

        if ( (int)$attrs->errors > 0 || (int)$attrs->failures > 0 ) {
            $this->log('Junit report has some errors or failures');
            $success = false;
        } else {
            $this->log('Junit report has not errors or failures');
            $success = true;
        }
        $this->project->setProperty($this->getPropertySuccess(), $success);
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
     * Gets the property name to store the number of tests.
     *
     * @return string
     */
    public function getPropertyTests()
    {
        return $this->propertyTests;
    }

    /**
     * Sets the property name to store the number of tests.
     *
     * @param string $propertyTests the property tests
     *
     * @return self
     */
    public function setPropertyTests($propertyTests)
    {
        $this->propertyTests = $propertyTests;

        return $this;
    }

    /**
     * Gets the property name to store the number of assertions.
     *
     * @return string
     */
    public function getPropertyAssertions()
    {
        return $this->propertyAssertions;
    }

    /**
     * Sets the property name to store the number of assertions.
     *
     * @param string $propertyAssertions the property assertions
     *
     * @return self
     */
    public function setPropertyAssertions($propertyAssertions)
    {
        $this->propertyAssertions = $propertyAssertions;

        return $this;
    }

    /**
     * Gets the property name to store the number of failures.
     *
     * @return string
     */
    public function getPropertyFailures()
    {
        return $this->propertyFailures;
    }

    /**
     * Sets the property name to store the number of failures.
     *
     * @param string $propertyFailures the property failures
     *
     * @return self
     */
    public function setPropertyFailures($propertyFailures)
    {
        $this->propertyFailures = $propertyFailures;

        return $this;
    }

    /**
     * Gets the property name to store the number of errors.
     *
     * @return string
     */
    public function getPropertyErrors()
    {
        return $this->propertyErrors;
    }

    /**
     * Sets the property name to store the number of errors.
     *
     * @param string $propertyErrors the property errors
     *
     * @return self
     */
    public function setPropertyErrors($propertyErrors)
    {
        $this->propertyErrors = $propertyErrors;

        return $this;
    }

    /**
     * Gets the property name to store the verification of success.
     *
     * @return string
     */
    public function getPropertySuccess()
    {
        return $this->propertySuccess;
    }

    /**
     * Sets the property name to store the verification of success.
     *
     * @param string $propertySuccess the property success
     *
     * @return self
     */
    public function setPropertySuccess($propertySuccess)
    {
        $this->propertySuccess = $propertySuccess;

        return $this;
    }
}