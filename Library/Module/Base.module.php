<?php 

class Base
{
	protected function send($socket, $signal)
	{
		fputs($socket, Core\Helpers\Str::trim($signal) . "\n");
		usleep(100000);
		echo "[SEND] $signal\n";
	}
	
	protected function privmsg($socket, $target, $message)
	{
		$this->send($socket, 'PRIVMSG ' . Core\Helpers\Str::trim($target) . ' :' . Core\Helpers\Str::trim($message));
		usleep(100000);
	}
	
	protected function notice($socket, $target, $message)
	{
		$this->send($socket, 'NOTICE ' . Core\Helpers\Str::trim($target) . ' :' . Core\Helpers\Str::trim($message));
		usleep(100000);
	}
}