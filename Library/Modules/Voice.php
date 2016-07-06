<?php

namespace Modules;

class Voice extends \Core\ModuleBase
{
	public $helpline = 'voices the given nickname, only usable by +h and above.';
	
	public $minAcl = 3;
	
	public function process(&$that, &$socket, $data, $input, $command, $args)
	{
		$sender = $that->sender($data);
		$channel = $that->channel($data);
		$input = explode(' ', $that->input($data));
		unset($input[0]);
		$input = implode(' ', $input);
		if($that->getLevel($sender, $channel) > 2 || $that->getLevel($sender, '', $that->host($data)) > 6)
		{
			if($that->getLevel($that->nick, $channel) > 2)
			{
				$this->send($socket, 'MODE ' . $channel . ' +v ' . $input);
				return;
			}
			$this->privmsg($socket, $channel, "{$sender}: I am not authorized to do that.");
			return;
		}
		else
		{
			$this->privmsg($socket, $channel, "{$sender}: You are not authorized to do that.");
		}
	}
}
