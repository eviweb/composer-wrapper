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
 * @package     evidev\composer
 * @author      Eric VILLARD <dev@eviweb.fr>
 * @copyright   (c) 2013 Eric VILLARD <dev@eviweb.fr>
 * @license     http://opensource.org/licenses/MIT MIT License
 */

namespace evidev\composer;

/**
 * composer wrapper class
 *
 * @package     evidev\composer
 * @author      Eric VILLARD <dev@eviweb.fr>
 * @copyright   (c) 2012 Eric VILLARD <dev@eviweb.fr>
 * @license     http://opensource.org/licenses/MIT MIT License
 */
final class Wrapper
{
    /**
     * url from where to get the composer phar archive
     */
    const PHAR_URL = 'https://getcomposer.org/composer.phar';

    /**
     * composer executable path
     *
     * @var string
     */
    private $composer;

    /**
     * Command-line application, created on demand and kept around for future
     * calls.
     *
     * @var \Composer\Console\Application
     */
    private $application;

    /**
     * Keeps track of whether we've already taken care of downloading
     * composer.phar.
     */
    private $pharLoaded;

    /**
     * Connects to getcomposer.org to fetch the latest composer.phar.
     * You don't normally need to call this directely, because the run() method
     * takes care of it, but it may be useful to fix a corrupted composer.phar
     * or force a fresh composer install.
     *
     * @param boolean $force TRUE to re-download composer.phar even if a valid
     *    file already exists.
     */
    public function loadComposerPhar($force = true)
    {
        if (!$force && !$this->pharLoaded &&
            file_exists($this->composer) &&
            is_readable($this->composer) &&
            filesize($this->composer)) {
            return true;
        }

        $this->pharLoaded = true;

        $phar = file_get_contents(static::PHAR_URL);
        if ($phar === false) {
                trigger_error(
                    "Downloading PHAR from getcomposer.org failed. " .
                    "Make sure that URL-fopen is supported on this system, " .
                    "and that your server has a working internet connection " .
                    "allowing outbound HTTPS connections.",
                    E_USER_WARNING);
            return false;
        }
        if (empty($phar)) {
            trigger_error(
                    "PHAR downloaded from getcomposer.org is empty. "  .
                    "There is no way this can work, but I don't know what " .
                    " to do about it.",
                    E_USER_WARNING);
            return false;
        }
        $bytes_written = file_put_contents($this->composer, $phar);
        if ($bytes_written === false) {
            trigger_error(
                    "Failed to write downloaded PHAR. ",
                    E_USER_WARNING);
            return false;
        }

        return true;
    }

    /**
     * Check whether the current setup meets the minimum memory requirements
     * for composer; raise a notice if not.
     */
    private function checkMemoryLimit() {
        if (function_exists('ini_get')) {
            /**
             * Note that this calculation is incorrect for memory limits that
             * exceed the value range of the underlying platform's native
             * integer.
             * In practice, we will get away with it, because it doesn't make
             * sense to configure PHP's memory limit to half the addressable
             * RAM (2 GB on a typical 32-bit system).
             */
            $memoryInBytes = function ($value) {
                    $unit = strtolower(substr($value, -1, 1));
                    $value = (int) $value;
                    switch ($unit) {
                        case 'g':
                            $value *= 1024 * 1024 * 1024;
                            break;
                        case 'm':
                            $value *= 1024 * 1024;
                            break;
                        case 'k':
                            $value *= 1024;
                            break;
                    }

                    return $value;
                };

            $memoryLimit = trim(ini_get('memory_limit'));
            // Increase memory_limit if it is lower than 512M
            if ($memoryLimit != -1 && $memoryInBytes($memoryLimit) < 512 * 1024 * 1024) {
                trigger_error("Configured memory limit ($memoryLimit) is lower " .
                              "than 512M; composer-wrapper may not work " .
                              "correctly. Consider increasing PHP's " .
                              "memory_limit to at least 512M.",
                              E_USER_NOTICE);
            }
        }

    }

    /**
     * constructor
     *
     * @param   string  $directory  target directory where to copy composer.phar
     */
    private function __construct($directory)
    {
        $this->composer = $directory . '/composer.phar';
        $this->application = null;
        $this->pharLoaded = false;

        if (!file_exists($this->composer) ||
            !is_readable($this->composer) ||
            !filesize($this->composer)) {
                if (!$this->loadComposerPhar()) {
                    // FIXME: downloading composer failed.
                    // This needs to be handled somewhere, somehow...
                    return;
                }
        }

        $this->checkMemoryLimit();

        if (!function_exists('includeIfExists')) {
            require_once 'phar://' . $this->composer . '/src/bootstrap.php';
        }
    }

    /**
     * Factory method.
     *
     * @param   string  $directory  target directory where to copy composer.phar
     *                              if it is not provided or if the directory
     *                              does not exist, it is initialized using
     *                              sys_get_temp_dir()
     * @return \evidev\composer\Wrapper
     */
    public static function create($directory = '')
    {
        if (empty($directory) || !file_exists($directory)) {
            $directory = sys_get_temp_dir();
        }
        return new Wrapper($directory);
    }

    /**
     * Run this composer wrapper as a command-line application.
     *
     * @param   string  $input  command line arguments
     * @param   object  $output output object
     * @return  integer 0 if everything went fine, or an error code
     * @see http://api.symfony.com/2.2/Symfony/Component/Console/Application.html#method_run
     */
    public function run($input = '', $output = null)
    {
        $this->loadComposerPhar(false);
        if (!$this->application) {
            $this->application = new \Composer\Console\Application();
            $this->application->setAutoExit(false);
        }

        $cli_args = is_string($input) && !empty($input) ?
                new \Symfony\Component\Console\Input\StringInput($input) :
                null;

        return $this->application->run(
            $cli_args,
            $output
        );
    }
}
