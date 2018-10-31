<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\Core\Console\AutomaticInstallation;
use Friendica\Test\Util\DBStructureMockTrait;
use org\bovigo\vfs\vfsStream;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @requires PHP 7.0
 */
class AutomaticInstallationConsoleTest extends ConsoleTest
{
	use DBStructureMockTrait;

	private $db_host;
	private $db_port;
	private $db_data;
	private $db_user;
	private $db_pass;

	private $assertFile;
	private $assertFileDb;

	public function setUp()
	{
		parent::setUp();

		if ($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php')) {
			$this->root->getChild('config')
				->removeChild('local.ini.php');
		}

		$this->db_host = getenv('MYSQL_HOST');
		$this->db_port = (!empty(getenv('MYSQL_PORT'))) ? getenv('MYSQL_PORT') : null;
		$this->db_data = getenv('MYSQL_DATABASE');
		$this->db_user = getenv('MYSQL_USERNAME') . getenv('MYSQL_USER');
		$this->db_pass = getenv('MYSQL_PASSWORD');

		$this->mockConfigGet('config', 'php_path', false);

		$this->assertFile  = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'ini' . DIRECTORY_SEPARATOR .
			'assert.ini.php';
		$this->assertFileDb  = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'ini' . DIRECTORY_SEPARATOR .
			'assert_db.ini.php';
	}

	private function assertFinished($txt, $withconfig = false, $copyfile = false)
	{
		$cfg = '';

		if ($withconfig) {
			$cfg = <<<CFG


Creating config file...

 Complete!
CFG;
		}

		if ($copyfile) {
			$cfg = <<<CFG


Copying config file...

 Complete!
CFG;
		}

		$finished = <<<FIN
Initializing setup...

 Complete!


Checking environment...

 NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.

 Complete!
{$cfg}


Checking database...

 Complete!


Inserting data into database...

 Complete!


Installing theme

 Complete



Installation is finished


FIN;
		$this->assertEquals($finished, $txt);
	}

	private function assertStuckDB($txt)
	{
		$finished = <<<FIN
Initializing setup...

 Complete!


Checking environment...

 NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.

 Complete!


Creating config file...

 Complete!


Checking database...

[Error] --------
: 


FIN;

		$this->assertEquals($finished, $txt);
	}

	/**
	 * @medium
	 */
	public function testWithConfig()
	{
		$this->mockConnect(true, 1);
		$this->mockConnected(true, 1);
		$this->mockExistsTable('user', false, 1);
		$this->mockUpdate([false, true, true], null, 1);

		$config = <<<CONF
<?php return <<<INI

[database]
hostname = 
username = 
password = 
database = 
charset = utf8mb4


; ****************************************************************
; The configuration below will be overruled by the admin panel.
; Changes made below will only have an effect if the database does
; not contain any configuration for the friendica system.
; ****************************************************************

[config]
admin_email =

sitename = Friendica Social Network

register_policy = REGISTER_OPEN
register_text =

[system]
default_timezone = UTC

language = en
INI;
// Keep this line

CONF;

		vfsStream::newFile('prepared.ini.php')
			->at($this->root)
			->setContent($config);

		$console = new AutomaticInstallation();
		$console->setOption('f', 'prepared.ini.php');

		$txt = $this->dumpExecute($console);

		$this->assertFinished($txt, false, true);

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php'));
	}

	/**
	 * @medium
	 */
	public function testWithEnvironmentAndSave()
	{
		$this->mockConnect(true, 1);
		$this->mockConnected(true, 1);
		$this->mockExistsTable('user', false, 1);
		$this->mockUpdate([false, true, true], null, 1);

		$this->assertTrue(putenv('FRIENDICA_ADMIN_MAIL=admin@friendica.local'));
		$this->assertTrue(putenv('FRIENDICA_TZ=Europe/Berlin'));
		$this->assertTrue(putenv('FRIENDICA_LANG=de'));
		$this->assertTrue(putenv('FRIENDICA_URL_PATH=/friendica'));

		$console = new AutomaticInstallation();
		$console->setOption('savedb', true);

		$txt = $this->dumpExecute($console);

		$this->assertFinished($txt, true);

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php'));

		$this->assertFileEquals(
			$this->assertFileDb,
			$this->root->getChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php')->url());
	}

	/**
	 * @medium
	 */
	public function testWithEnvironmentWithoutSave()
	{
		$this->mockConnect(true, 1);
		$this->mockConnected(true, 1);
		$this->mockExistsTable('user', false, 1);
		$this->mockUpdate([false, true, true], null, 1);

		$this->assertTrue(putenv('FRIENDICA_ADMIN_MAIL=admin@friendica.local'));
		$this->assertTrue(putenv('FRIENDICA_TZ=Europe/Berlin'));
		$this->assertTrue(putenv('FRIENDICA_LANG=de'));
		$this->assertTrue(putenv('FRIENDICA_URL_PATH=/friendica'));

		$console = new AutomaticInstallation();

		$returnStr = $this->dumpExecute($console);

		$this->assertFinished($returnStr, true);

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php'));

		$this->assertFileEquals(
			$this->assertFile,
			$this->root->getChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php')->url());
	}

	/**
	 * @medium
	 */
	public function testWithArguments()
	{
		$this->mockConnect(true, 1);
		$this->mockConnected(true, 1);
		$this->mockExistsTable('user', false, 1);
		$this->mockUpdate([false, true, true], null, 1);

		$console = new AutomaticInstallation();

		$console->setOption('dbhost', $this->db_host);
		$console->setOption('dbuser', $this->db_user);
		if (!empty($this->db_pass)) {
			$console->setOption('dbpass', $this->db_pass);
		}
		if (!empty($this->db_port)) {
			$console->setOption('dbport', $this->db_port);
		}
		$console->setOption('dbdata', $this->db_data);

		$console->setOption('admin', 'admin@friendica.local');
		$console->setOption('tz', 'Europe/Berlin');
		$console->setOption('lang', 'de');

		$console->setOption('urlpath', '/friendica');

		$returnStr = $this->dumpExecute($console);

		$this->assertFinished($returnStr, true);

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php'));

		$this->assertFileEquals(
			$this->assertFileDb,
			$this->root->getChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php')->url());
	}

	/**
	 * @runTestsInSeparateProcesses
	 * @preserveGlobalState disabled
	 */
	public function testNoDatabaseConnection()
	{
		$this->mockConnect(false, 1);

		$console = new AutomaticInstallation();

		$returnStr = $this->dumpExecute($console);

		$this->assertStuckDB($returnStr);
	}

	public function testGetHelp()
	{
		// Usable to purposely fail if new commands are added without taking tests into account
		$theHelp = <<<HELP
Installation - Install Friendica automatically
Synopsis
	bin/console autoinstall [-h|--help|-?] [-v] [-a] [-f]

Description
    Installs Friendica with data based on the local.ini.php file or environment variables

Notes
    Not checking .htaccess/URL-Rewrite during CLI installation.

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.
    -a                      All setup checks are required (except .htaccess)
    -f|--file <config>      prepared config file (e.g. "config/local.ini.php" itself) which will override every other config option - except the environment variables)
    -s|--savedb             Save the DB credentials to the file (if environment variables is used)
    -H|--dbhost <host>      The host of the mysql/mariadb database (env MYSQL_HOST)
    -p|--dbport <port>      The port of the mysql/mariadb database (env MYSQL_PORT)
    -d|--dbdata <database>  The name of the mysql/mariadb database (env MYSQL_DATABASE)
    -U|--dbuser <username>  The username of the mysql/mariadb database login (env MYSQL_USER or MYSQL_USERNAME)
    -P|--dbpass <password>  The password of the mysql/mariadb database login (env MYSQL_PASSWORD)
    -u|--urlpath <url_path> The URL path of Friendica - f.e. '/friendica' (env FRIENDICA_URL_PATH) 
    -b|--phppath <php_path> The path of the PHP binary (env FRIENDICA_PHP_PATH) 
    -A|--admin <mail>       The admin email address of Friendica (env FRIENDICA_ADMIN_MAIL)
    -T|--tz <timezone>      The timezone of Friendica (env FRIENDICA_TZ)
    -L|--lang <language>    The language of Friendica (env FRIENDICA_LANG)
 
Environment variables
   MYSQL_HOST                  The host of the mysql/mariadb database (mandatory if mysql and environment is used)
   MYSQL_PORT                  The port of the mysql/mariadb database
   MYSQL_USERNAME|MYSQL_USER   The username of the mysql/mariadb database login (MYSQL_USERNAME is for mysql, MYSQL_USER for mariadb)
   MYSQL_PASSWORD              The password of the mysql/mariadb database login
   MYSQL_DATABASE              The name of the mysql/mariadb database
   FRIENDICA_URL_PATH          The URL path of Friendica (f.e. '/friendica')
   FRIENDICA_PHP_PATH          The path of the PHP binary
   FRIENDICA_ADMIN_MAIL        The admin email address of Friendica (this email will be used for admin access)
   FRIENDICA_TZ                The timezone of Friendica
   FRIENDICA_LANG              The langauge of Friendica
   
Examples
	bin/console autoinstall -f 'input.ini.php
		Installs Friendica with the prepared 'input.ini.php' file

	bin/console autoinstall --savedb
		Installs Friendica with environment variables and saves them to the 'config/local.ini.php' file

	bin/console autoinstall -h localhost -p 3365 -U user -P passwort1234 -d friendica
		Installs Friendica with a local mysql database with credentials

HELP;

		$console = new AutomaticInstallation();
		$console->setOption('help', true);

		$txt = $this->dumpExecute($console);

		$this->assertEquals($txt, $theHelp);
	}
}
