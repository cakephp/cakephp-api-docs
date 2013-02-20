# CakePHP API docs #

The CakePHP API docs allow you to build the API documentation as seen on api.cakephp.org.
These API docs are built with [apigen](https://github.com/apigen/apigen). Thanks to the Apigen
team for producing a great tool.

Requirements:

* PHP 5.3.7 or greater.
* Make
* A clone of [CakePHP](https://github.com/cakephp/cakephp)

## Building the documentation ##

To build the documentation you will need to use `make`. You can build all the documentation using:

    make build-all

Or you can build one version by using:

    make build-2.0

Where the trailing version number is the major.minor release to build.
