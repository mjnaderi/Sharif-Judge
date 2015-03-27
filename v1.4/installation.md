Installation
============

Requirements
------------

For running Sharif Judge, a Linux server with following requirements is needed:

* Webserver running PHP version 5.3 or later
* PHP CLI (PHP command line interface, i.e. `php` shell command)
You should be able to run `php` from command line. In Ubuntu you need to install `php5-cli` package.
* MySql database (with `mysqli` extension for PHP) or PostgreSql database
* PHP must have permission to run shell commands using [shell_exec](http://www.php.net/manual/en/function.shell-exec.php) function.
For example this command should run correctly:
```php
echo shell_exec("php -v");
```
* Tools for compiling and running submitted codes (`gcc`, `g++`, `javac`, `java`, `python2`, `python3` commands)
* It is better to have `perl` installed for more precise time and memory limit and imposing size limit on output of submitted code.

Installation
------------

* 1. Download the latest release from [download page](https://github.com/mjnaderi/Sharif-Judge/releases) and unpack downloaded file in your public html directory.
* 2. **[Optional]** Move folders `system` and `application` somewhere outside your public directory, and enter their full path in `index.php` file.
```php
$system_path = '/home/mohammad/secret/system';
$application_folder = '/home/mohammad/secret/application';
```
* 3. Create a MySql or PostgreSql database for Sharif Judge. Do not install any database connection package for C/C++, Java or Python.
* 4. Set database connection settings in file `application/config/database.php`. You can use a prefix for your table names.
```php
/*  Enter database connection settings here:  */
'dbdriver' => 'postgre',    // database driver (mysqli, postgre)
'hostname' => 'localhost',  // database host
'username' => `,           // database username
'password' => `,           // database password
'database' => `,           // database name
'dbprefix' => 'shj_',       // table prefix
/**********************************************/
```
* 5. Make `application/cache/Twig` writable by php.
* 6. Open Sharif Judge's main page in a web browser and follow the installation process.
* 7. Log in with your admin account.
* 8.
    **[IMPORTANT]** Move folders `tester` and `assignments` somewhere outside your public directory. Then save their full path in `Settings` page. **These two folders must be writable by PHP.** Submitted files will be stored in `assignments` folder. So it should be somewhere not publicly accessible.

Important: [Secure Sharif Judge](security.md)

After Installation
------------------

Now that you have installed Sharif Judge, you may want to:

  * [Add Assignment](add_assignment.md)
  * Change settings (time zone, upload size limit, ...)
  * [Add Users](users.md#add_users)
  * **[IMPORTANT]** [Secure Sharif Judge](security.md)
  * **[IMPORTANT]** Enable [Sandbox](sandboxing.md)
  * Learn about [Shield](shield.md)
  * [Enable clean URLs](clean_urls.md)
