<?php 

chdir(__DIR__);
set_time_limit(0);
error_reporting(E_ALL);

echo "[INFO] Linting files...\n";
$cmd = 'find . -name "*.php" -print0 | xargs -0 -n1 -P8 php -l 2>&1 >/dev/null; echo $?';
$cmd = explode("\n", trim(system($cmd)));
$lint = trim(@end($cmd));

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

while(true)
{	
	if($data = $bot->listen())
	{
		$bot->process($data);
	}
}

$bot->disconnect();