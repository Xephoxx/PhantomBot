<?php 

class Autoloader
{
	public static function load($className)
	{
		$namespace = str_replace('\\', '/', __NAMESPACE__);
		$className = str_replace('\\', '/', $className);
		$classPath = 'Library/' . (!empty($namespace) ? $namespace : '') . $className . '.class.php';
		
		if(!file_exists($classPath))
		{
			echo '[WARN] Missing: ', $classPath . PHP_EOL;
			exit;
		}
		
		echo '[INFO] Loading: ' . $classPath . PHP_EOL;
		require $classPath;
	}
}

/*
class Autoloader
{
	public static function load($class)
	{
		$class = str_replace('\\', '/', $class);
		$filename = __DIR__ . '/' . $class . (strpos($class, 'Helpers')?'.helper':'') . '.php';	

		if(file_exists($filename))
		{
			return require $filename;
		}
		
		throw new Exception('File: "' . $filename . '" not found.');
	}
}
*/