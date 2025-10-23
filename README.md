# CakePHP API docs #

The CakePHP API docs allow you to build the API documentation as seen on
https://api.cakephp.org



### Running documentation generator on a CakePHP codebase

The tool generates documentation from CakePHP source code. According to the Makefile, you need:
- A local clone of the CakePHP repository (default location: ../cakephp/)
- Run the generator command

For example, to test with CakePHP 5.2:

#### Ensure you have a CakePHP clone
```
cd ../cakephp/ && git checkout origin/5.x && cd -
```

#### Run the generator
```
php bin/apitool.php generate --config cakephp5 --version 5.2 --tag origin/5.x --output-dir build/test/ ../cakephp
```

