<?php

namespace Core\Helpers;

class Sql extends SQLite3
{
	private static $instance;
	
	private function __construct() 
	{ }
	
	private function __clone()
	{ }
	
	public static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public function initDb($file = null)
	{
		if(is_null($file))
		{
			echo '[WARN] Sql->initDb($file = null), Please correct this.' . PHP_EOL;
			exit;
		}
		
		$this->open($file);
	}
}