Composer Wrapper
================
    
This project aims to provide a way to use composer from within a script,
even if it is not installed.
    
[![Build Status](https://travis-ci.org/eviweb/composer-wrapper.png?branch=master)](https://travis-ci.org/eviweb/composer-wrapper)
    
How to install :
----------------
You can choose between :    
1.    clone this repo ```git clone https://github.com/eviweb/composer-wrapper.git```    
2.    use composer by adding ```"eviweb/composer-wrapper" : "1.*"``` to the _require_ section of your _composer.json_    
3.    directly download the [Wrapper.php](https://raw.github.com/eviweb/composer-wrapper/master/src/evidev/composer/Wrapper.php)   
4.    dynamic install _see below_
    
How to use :
------------
### Include the wrapper
First, you need to include the wrapper into your code,
according to the previous installation choice :    
    
1.    add ```
require_once 'PATH_TO_COMPOSER_WRAPPER_DIRECTORY/src/evidev/composer/Wrapper.php';
``` where _PATH_TO_COMPOSER_WRAPPER_DIRECTORY_ is the path to the cloned repository    
2.    add ```
require 'vendor/autoload.php';
``` please refer to the [Composer Documentation](http://getcomposer.org/doc/00-intro.md#autoloading)    
3.    add ```
require_once 'PATH_TO_COMPOSER_WRAPPER_FILE';
``` where _PATH_TO_COMPOSER_WRAPPER_FILE_ is the path to the _Wrapper.php_ file    
4.    add    
    
>        $wrapper_file = sys_get_temp_dir() . '/Wrapper.php';
>        if (!file_exists($wrapper_file)) {
>             file_put_contents(
>                 $wrapper_file,
>                 file_get_contents('https://raw.github.com/eviweb/composer-wrapper/master/src/evidev/composer/Wrapper.php')
>             );
>        }
>        require_once $wrapper_file;    
     
     
### Use the wrapper
1.    for a command line use, simply add ```exit(\evidev\composer\Wrapper::create()->run());``` to your executable script    
2.    if you want to use it in the body of your script, and pass specific arguments to the _Wrapper::run()_ method you need to do as the following :    
    
>        $wc = new \evidev\composer\Wrapper::create();
>        $exit_code = $wc->run("COMPOSER_OPTION_OR_COMMAND_AS_STRING");
>        // add more code here for example
>        exit($exit_code);    
     
where _COMPOSER_OPTION_OR_COMMAND_AS_STRING_ is a composer option or command
