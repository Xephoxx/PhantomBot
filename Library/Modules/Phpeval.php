<?php 

namespace Modules;

class Phpeval extends \Core\ModuleBase
{
	public $helpline = 'evals PHP code from given input!';
	
	public $minAcl = 1;
	
	public function process(&$that, &$socket, $data, $input, $command, $args)
	{
		$sender = $that->sender($data);
		$channel = $that->channel($data);
		
		//print_r($args);
		$input = explode(' ', $that->input($data));
		unset($input[0]);
		$input = implode(' ', $input);
		
		$safephp = new \Core\Helpers\Safephp();
		if($safephp->parse($input))
		{
			$safephp->evaluate($input);
			$this->privmsg($socket, $channel, '[PHP] ' . $safephp->output);
		}
		else
		{
			$this->privmsg($socket, $channel, '[PHP] You have an error in your code!');
		}
	}
}