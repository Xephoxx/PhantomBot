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
		
		$input = explode(' ', $that->input($data));
		unset($input[0]);
		$input = implode(' ', $input);
		
		$input = stripslashes($input);
		$input = stripcslashes($input);
		
		$se = new \Core\Helpers\Safereval();
		$GLOBALS = NULL;
		$_GLOBALS = NULL;
		$errors = $se->checkScript($input, 1);
		if(!is_array($errors))
		{
			if(strlen($se->output))
			{
				$this->privmsg($socket, $channel, '[PHP] ' . $se->output);
			}
			else
			{
				$this->privmsg($socket, $channel, '[PHP] There was no output from your code!');
			}
		}
		else
		{
			$errors = explode('|||', $se->errors($errors));
			
			foreach($errors as $error)
			{
				if(strlen($error))
				{
					$this->privmsg($socket, $channel, '[PHP] ' . $error);
				}
			}
		}
	}
}