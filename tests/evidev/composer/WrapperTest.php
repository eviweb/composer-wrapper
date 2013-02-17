<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * The MIT License
 *
 * Copyright 2013 Eric VILLARD <dev@eviweb.fr>.
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
 * @copyright	(c) 2013 Eric VILLARD <dev@eviweb.fr>
 * @license     http://opensource.org/licenses/MIT MIT License
 */

namespace evidev\composer\test;
use \evidev\composer\Wrapper;

/**
 * composer wrapper test class
 * 
 * @package     evidev\composer\test
 * @author      Eric VILLARD <dev@eviweb.fr>
 * @copyright	(c) 2012 Eric VILLARD <dev@eviweb.fr>
 * @license     http://opensource.org/licenses/MIT MIT License
 */
class WrapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * associative array of needed files
     * 
     * @var array
     */
    protected static $files;
    
    /**
     * initializes the test environment
     */
    public static function setUpBeforeClass()
    {
        static::$files = array(
            'composer'  => sys_get_temp_dir().'/composer.phar',
            'stream'    => sys_get_temp_dir().'/wrapper-unittest.stream',
        );
    }
    
    /**
     * clears the test environment
     */
    public static function tearDownAfterClass()
    {
        foreach(static::$files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * @covers \evidev\composer\Wrapper::create
     */
    public function testCreate()
    {
        $cw = Wrapper::create();
        $this->assertInstanceOf('\\evidev\\composer\\Wrapper', $cw);
        $this->assertFileExists(static::$files['composer']);
    }

    /**
     * @covers \evidev\composer\Wrapper::run
     */
    public function testRun()
    {
        $file = static::$files['stream'];
        $cw = Wrapper::create();
        $rs = fopen($file, 'w', false);
        $stream = new \Symfony\Component\Console\Output\StreamOutput($rs);        
        $this->assertEquals(0, $cw->run("-V", $stream));
        fclose($rs);
        $this->assertTrue(
            (boolean) preg_match('/^Composer version/', file_get_contents($file))
        );
    }
    
    /**
     * @covers \evidev\composer\Wrapper::run
     */
    public function testCLIRun()
    {
        $_SERVER['argv'][] = '-V';
        $file = static::$files['stream'];
        $cw = Wrapper::create();
        $rs = fopen($file, 'w', false);
        $stream = new \Symfony\Component\Console\Output\StreamOutput($rs);        
        $this->assertEquals(0, $cw->run("", $stream));
        fclose($rs);
        $this->assertTrue(
            (boolean) preg_match('/^Composer version/', file_get_contents($file))
        );
    }
}
