<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * The MIT License
 *
 * Copyright 2015 Eric VILLARD <dev@eviweb.fr>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package     evidev\composer\test
 * @author      Eric VILLARD <dev@eviweb.fr>
 * @copyright   (c) 2015 Eric VILLARD <dev@eviweb.fr>
 * @license     http://opensource.org/licenses/MIT MIT License
 */

namespace evidev\composer\test;

use evidev\composer\Wrapper;
use PHPUnit_Framework_TestCase;

/**
 * ComposerSelfupdateIssueTest
 *
 * @package     evidev\composer\test
 * @author      Eric VILLARD <dev@eviweb.fr>
 * @copyright   (c) 2015 Eric VILLARD <dev@eviweb.fr>
 * @license     http://opensource.org/licenses/MIT MIT License
 */
class ComposerSelfupdateIssueTest extends PHPUnit_Framework_TestCase
{
    /**
     * associative array of needed files
     *
     * @var array
     */
    protected $files;

    /**
     * initializes the test environment
     */
    public function setUp()
    {
        $this->files = array(
            'composer'      => sys_get_temp_dir().'/composer.phar',
            'selfupdater'   => sys_get_temp_dir().'/composerselfupdateissuetest.php',
        );
    }

    /**
     * clears the test environment
     */
    public function tearDown()
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @see https://github.com/eviweb/composer-wrapper/issues/5
     */
    public function testWrapperMustNotAlterHostFileDuringComposerSelfupdate()
    {
        $this->assertTrue($this->copyComposer());
        $this->assertTrue($this->createSelfupdater());

        $result = $this->executeScript($this->files['selfupdater']);

        $this->assertEquals(0, $result['status']);
        $this->assertEquals(
            $this->getSelfupdaterContent(),
            file_get_contents($this->files['selfupdater'])
        );
        $this->assertNotEquals(
            $this->executeScript($this->getComposerFixurePath(), '-V'),
            $this->executeScript($this->files['composer'], '-V')
        );
    }

    /**
     * @see https://github.com/eviweb/composer-wrapper/issues/7
     */
    public function testSelfupdateFixMustAlterHostScriptReferenceOnlyTemporarily()
    {
        $argv0 = $_SERVER['argv'][0];
        Wrapper::create(dirname($this->files['composer']))->run('self-update');

        $this->assertEquals($argv0, $_SERVER['argv'][0]);
    }

    /**
     * create the composer selfupdater script
     *
     * @return boolean returns true if the file is created or false
     */
    private function createSelfupdater()
    {
        return file_put_contents(
            $this->files['selfupdater'],
            $this->getSelfupdaterContent()
        ) !== false;
    }

    /**
     * get the content of the composer selfupdater script
     *
     * @return string returns the content of the php file
     */
    private function getSelfupdaterContent()
    {
        $path = $this->getWrapperPath();
        assert(file_exists($path), "Wrapper file should be found at: $path");

        return <<<FILE
<?php
    require_once "$path";

    evidev\composer\Wrapper::create()->run("selfupdate");

FILE;
    }

    /**
     * get the wrapper path
     *
     * @return string returns the realpath of the wrapper
     */
    private function getWrapperPath()
    {
        return realpath(__DIR__.'/../../../src/evidev/composer/Wrapper.php');
    }

    /**
     * get the composer fixture path
     *
     * @return string returns the realpath of the composer fixture
     */
    private function getComposerFixurePath()
    {
        return realpath(__DIR__.'/../../fixtures/composer.phar');
    }

    /**
     * execute a php script
     *
     * @param  string $script script path
     * @param  string $args   string of command arguments
     * @return array  returns an associative array:
     *                       - status: integer, the status code of the command
     *                       - output: array, the ouptut array of the command
     * @see http://php.net/manual/en/function.exec.php for the output description
     */
    private function executeScript($script, $args = '')
    {
        assert(file_exists($script), "Script to be executed should be found at: $script");

        $output = array();
        exec(PHP_BINARY.' '.$script.' '.$args, $output, $status);

        return array(
            'status' => $status,
            'output' => $output
        );
    }

    /**
     * copy the composer.phar fixture
     *
     * @return boolean returns true if the file is well duplicated or false
     */
    private function copyComposer()
    {
        $path = $this->getComposerFixurePath();
        assert(file_exists($path), "Composer fixture should be found at: $path");

        return copy($path, $this->files['composer']);
    }
}
