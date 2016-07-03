<?php

namespace Modules;

class Restart extends \Core\ModuleBase
{
	public $helpline = 'restarts the bot.';
	
	public $minAcl = 8;	

	public function process(&$that, &$socket, $data, $input, $command, $args)
	{
		$sender = $that->sender($data);
		$channel = $that->channel($data);
		$input = explode(' ', $that->input($data));
		unset($input[0]);
		$input = implode(' ', $input);
		if($that->getLevel($sender, '', $that->host($data)) > 7)
		{
			$this->send($socket, 'QUIT :' . (($input !== '')?$input:'I\'ll Be Back...'));
			global $argv;
			$_ = $_SERVER['_'];
			pcntl_exec($_, $argv);
		}
		else
		{
			$this->privmsg($socket, $channel, "{$sender}: You are not authorized to do that.");
		}
	}
}