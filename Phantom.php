<?php 

chdir(__DIR__);
set_time_limit(0);
error_reporting(E_ALL);

echo "[INFO] Linting files...\n";
$cmd = 'find . -name "*.php" -print0 | xargs -0 -n1 -P8 php -l 2>&1 >/dev/null; echo $?';
$lint = trim(@end(explode("\n", trim(system($cmd)))));

echo '[INFO] Status: ' . ($lint == 0 ? 'ok' : 'bad') . "\n";
if($lint != 0)
{
	exit;
}

if(!file_exists('Config/Configuration.php'))
{
	echo '[WARN] Configuration missing, please see dir:Config/' . PHP_EOL;
	exit;
}
$config = require 'Config/Configuration.php';

require 'Library/Autoloader.php';
spl_autoload_register('Autoloader::load');

$shmop = shmop_open(0xff4, "c", 0644, 1);
shmop_write($shmop, '0', 0);

$bot = new Core\PhantomCore($shmop, $config);

$bot->load(true);

class Rehash
{
	public function __construct($file)
	{
		runkit_import($file, RUNKIT_IMPORT_CLASSES | RUNKIT_IMPORT_OVERRIDE);
	}
}

while(true)
{
	if(shmop_read($shmop, 0, 1) == 1)
	{
		new Rehash('Library/Core/PhantomCore.php');
		shmop_write($shmop, '1', 0);
	}
	
	if($data = $bot->listen())
	{
		$bot->process($data);
	}
}

$bot->disconnect();