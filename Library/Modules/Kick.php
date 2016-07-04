<?php

namespace Modules;

class Kick extends \Core\ModuleBase
{
	public $helpline = 'ops the given nickname, only usable by +o and above.';
	
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
			$this->send($socket, 'KICK ' . $channel . ' ' . $input);
			return;
		}
		else
		{
			$this->privmsg($socket, $channel, "{$sender}: You are not authorized to do that.");
		}
	}
}
