<?php 

namespace Modules;

class Pyeval extends \Core\ModuleBase
{
	public $helpline = 'evals Python code from given input!';
	
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
		
		$data = file_get_contents('http://eval.appspot.com/eval?statement=' . urlencode($input));
		
		$lines = explode("\n", $data);		
		foreach($lines as $line)
		{
			$this->privmsg($socket, $channel, '[PY] ' . (strlen($data)?$line:'There was no output from your code!'));
		}
	}
}