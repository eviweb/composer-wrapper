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
     * constructor
     *
     * @param   string  $directory  target directory where to copy composer.phar
     */
    private function __construct($directory)
    {
        $this->composer = $directory . '/composer.phar';
        if (!file_exists($this->composer)) {
            file_put_contents($this->composer, file_get_contents(static::PHAR_URL));
        }
        // this part has been directly taken from the bin/composer file
        error_reporting(-1);

        if (function_exists('ini_set')) {
            @ini_set('display_errors', 1);

            $memoryInBytes = function ($value) {
                    $unit = strtolower(substr($value, -1, 1));
                    $value = (int) $value;
                    switch ($unit) {
                        case 'g':
                            $value *= 1024;
                        // no break (cumulative multiplier)
                        case 'm':
                            $value *= 1024;
                        // no break (cumulative multiplier)
                        case 'k':
                            $value *= 1024;
                    }

                    return $value;
                };

            $memoryLimit = trim(ini_get('memory_limit'));
            // Increase memory_limit if it is lower than 512M
            if ($memoryLimit != -1 && $memoryInBytes($memoryLimit) < 512 * 1024 * 1024) {
                @ini_set('memory_limit', '512M');
            }
            unset($memoryInBytes, $memoryLimit);
        }

        //
        if (!function_exists('includeIfExists')) {
            require_once 'phar://' . $this->composer . '/src/bootstrap.php';
        }
    }

    /**
     * factory method
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
     * runs composer application
     *
     * @param   string  $input  command line arguments
     * @param   object  $output output object
     * @return  integer 0 if everything went fine, or an error code
     * @see http://api.symfony.com/2.2/Symfony/Component/Console/Application.html#method_run
     */
    public function run($input = '', $output = null)
    {
        $application = new \Composer\Console\Application();
        $application->setAutoExit(false);
        $cli_args = is_string($input) && !empty($input) ?
                new \Symfony\Component\Console\Input\StringInput($input) :
                null;

        return $application->run(
            $cli_args,
            $output
        );
    }
}
