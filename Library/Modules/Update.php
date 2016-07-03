<?php

namespace Modules;

class Update extends \Core\ModuleBase
{
	public $helpline = 'updates the bot.';
	
	public function process(&$that, &$socket, $data, $input, $command, $args)
	{
		$sender = $that->sender($data);
		$channel = $that->channel($data);
		if($that->getLevel($sender, '', $that->host($data)) > 7)
		{
			$this->privmsg($socket, $channel, 'Update: Checking for bot updates!');
			$null = shell_exec('git stash 2>&1');
			$update = shell_exec('git pull --progress 2>&1');
			
			if(preg_match("/up to date/i", $update) || preg_match("/up-to-date/i", $update))
			{
				$this->privmsg($socket, $channel, 'Update: Bot is Already Up to Date');
			}
			elseif(preg_match("/error/i", $update))
			{
				$this->privmsg($socket, $channel, 'Update: There was an Error Updating the Bot');
				$this->privmsg($socket, $channel, $update);
			}
			else
			{
				$this->privmsg($socket, $channel, 'Update: Bot Was Updated Successfully');
				$this->send($socket, 'QUIT :Updating...');
				global $argv;
				$_ = $_SERVER['_'];
				pcntl_exec($_, $argv);
			}
		}
		else
		{
			$this->privmsg($socket, $channel, "{$sender}: You are not authorized to do that.");
		}
	}
}