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

		$data = file_get_contents('http://phpcodechecker.com/api/?code=' . urlencode($input));
		$data = json_decode($data, 1);
		//print_r($data);

		$se = new \Core\Helpers\Safereval();
		if(!$data['errors'])
		{
			$GLOBALS = NULL;
			$_GLOBALS = NULL;
			$se->checkScript($input, 1);
			if(strlen($se->output)>1)
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
			if(isset($data['syntax']['message']))
			{
				
				$errors = array($data['syntax']['message']);
			}
			else
			{
				$errors = $se->checkScript($input, 0);
				$errors = explode('|||', $se->errors($errors));
			}
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