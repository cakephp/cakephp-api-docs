# CakePHP API docs #

The CakePHP API docs allow you to build the API documentation as seen on
api.cakephp.org.  These API docs are built with
[apigen](https://github.com/apigen/apigen). Thanks to the Apigen team for
producing a great tool.

Requirements:

* PHP 5.3.7 or greater.
* Make
* A clone of [CakePHP](https://github.com/cakephp/cakephp)
* git


## Installation

After cloning the repository make sure you install the git submodules:

    git submodule init
    git submodule update

After installing the submodules, you'll need to have a clone of cakephp/cakephp
as well. Bulding the docs uses the CakePHP source code, and will modify which tag/reference
is currently checked out.


## Building the documentation ##

To build the documentation you will need to use `make`. You can build all the
documentation using:

    make build-all

Or you can build one version by using:

    make build-2.0

Where the trailing version number is the major.minor release to build. The
following versions can be built:

* 1.2
* 1.3
* 2.0
* 2.1
* 2.2
* 2.3
* 2.4
* 2.5

By default the api-docs assume that `../cakephp` is a git clone of CakePHP.
Also documentation will be output to `./build/api`. If you want to change
these directories you can use the `SOURCE_DIR` and `BUILD_DIR` directories:

    make build-2.0 SOURCE_DIR=../cake BUILD_DIR=../api-output

Is an example of using custom directories.
