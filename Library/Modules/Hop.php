<?php

namespace Modules;

class Hop extends \Core\ModuleBase
{
	public $helpline = 'half ops the given nickname, only usable by +o and above.';
	
	public function process(&$that, &$socket, $data, $input, $command, $args)
	{
		$sender = $that->sender($data);
		$channel = $that->channel($data);
		$input = explode(' ', $that->input($data));
		unset($input[0]);
		$input = implode(' ', $input);
		if($that->getLevel($sender, $channel) > 3 || $that->getLevel($sender, '', $that->host($data)) > 6)
		{
			$this->send($socket, 'MODE ' . $channel . ' +h ' . $input);
			return;
		}
		else
		{
			$this->privmsg($socket, $channel, "{$sender}: You are not authorized to do that.");
		}
	}
}
