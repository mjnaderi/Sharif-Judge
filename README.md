# Sharif Judge

[Sharif Judge](http://sharifjudge.ir) is a free and open source online judge for C, C++, Java and
Python programming courses.

The web interface is written in PHP (CodeIgniter framework) and the main backend is written in BASH.

Python in Sharif Judge is not sandboxed yet. Just a low level of security is provided for python.
If you want to use Sharif Judge for python, USE IT AT YOUR OWN RISK or provide sandboxing yourself.

The full documentation is at [http://docs.sharifjudge.ir](http://docs.sharifjudge.ir)

Download the latest release from [http://sharifjudge.ir/download](http://sharifjudge.ir/download)

## Features
  * Multiple user roles (admin, head instructor, instructor, student)
  * Sandboxing _(not yet for python)_
  * Cheat detection (similar codes detection) using [Moss](http://theory.stanford.edu/~aiken/moss/)
  * Custom rule for grading late submissions
  * Submission queue
  * Download results in excel file
  * Download submitted codes in zip file
  * _"Output Comparison"_ and _"Tester Script"_ methods for checking output correctness
  * Add multiple users
  * Problem Descriptions (Editable in Markdown and HTML formats)
  * Rejudge
  * Scoreboard
  * Notifications
  * ...

## Requirements

For running Sharif Judge, a Linux server with following requirements is needed:

  * Webserver running PHP version 5.3 or later with `mysqli` extension
  * PHP CLI (PHP command line interface, i.e. `php` shell command)
  * MySql or PostgreSql database
  * PHP must have permission to run shell commands using [`shell_exec()`](http://www.php.net/manual/en/function.shell-exec.php) php function (specially `shell_exec("php");`)
  * Tools for compiling and running submitted codes (`gcc`, `g++`, `javac`, `java`, `python` and `python3` commands)
  * It is better to have `perl` installed for more precise time and memory limit and imposing size limit on output of submitted code.

## Installation

  1. Download the latest release from [download page](http://sharifjudge.ir/download) and unpack downloaded file in your public html directory.
  2. Create a MySql or PostgreSql database for Sharif Judge.
  3. Set database connection settings in `application/config/database.php`.
  4. Make `application/cache/Twig` writable by php.
  5. Open the main page of Sharif Judge in a web browser and follow the installation process.
  6. Log in with your admin account.
  7. **[IMPORTANT]** Move folders `tester` and `assignments` somewhere outside your public directory. Then save their full path in `Settings` page. **These two folders must be writable by PHP.** Submitted files will be stored in `assignments` folder. So it should be somewhere not publicly accessible.
  8. **[IMPORTANT]** [Secure Sharif Judge](http://docs.sharifjudge.ir/security)

## After Installation

  * Read the [documentation](http://docs.sharifjudge.ir/installation#after_installation)
