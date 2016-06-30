<?php 

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