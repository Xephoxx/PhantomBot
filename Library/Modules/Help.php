<?php 

namespace Modules;

class Help extends \Core\ModuleBase
{
	public $helpline = 'returns a list of all the available commands. %help command returns help about that command.';
	
	public $minAcl = 1;	

	public function __construct($that)
	{
		$this->helpline = "returns a list of all the available commands. {$that->prefix}help command returns help about that command.";
	}
	
	public function process(&$that, &$socket, $data, $input, $command, $args){
		$sender = $that->sender($data);
		$channel = $that->channel($data);
		if(empty($args))
		{
			$commands = array('normal'=>array(), 'hop'=>array(), 'op'=>array(), 
									'protect'=>array(), 'owner'=>array(), 'admin'=>array(), 'super'=>array());
			foreach($that->modules as $key => $module)
			{
				$module = strtolower(get_class($module));
				if(!isset($that->modules[$key]->noCommand))
				{
					switch(intval($that->modules[$key]->minAcl))
					{
						default:
						case 1:
						case 2:
							// Normal
							$commands['normal'][] = $module;
						break;
						
						case 3:
							// Hoped
							$commands['hop'][] = $module;
						break;
						
						case 4:
							// Oped
							$commands['op'][] = $module;
						break;
						
						case 5:
							// Protected
							$commands['protect'][] = $module;
						break;
						
						case 6:
							// Ownered
							$commands['owner'][] = $module;
						break;
						
						case 7:
							// Admined
							$commands['admin'][] = $module;
						break;
						
						case 8:
							// SuperAdmined
							$commands['super'][] = $module;
						break;
					}
				}
			}
			
			/*
			foreach($that->modules_alias as $alias => $module)
			{
					if($that->modules[$alias]->minAcl <= 2)
					{
						// Normal
						$commands['normal'][] = $alias;
					}
					elseif($that->modules[$alias]->minAcl == 3)
					{
						// Hoped
						$commands['hop'][] = $alias;
					}
					elseif($that->modules[$alias]->minAcl == 4)
					{
						// Oped
						$commands['op'][] = $alias;
					}
					elseif($that->modules[$alias]->minAcl == 5)
					{
						// Protected
						$commands['protect'][] = $alias;
					}
					elseif($that->modules[$alias]->minAcl == 6)
					{
						// Ownered
						$commands['owner'][] = $alias;
					}
					elseif($that->modules[$alias]->minAcl == 7)
					{
						// Admined
						$commands['admin'][] = $alias;
					}
					elseif($that->modules[$alias]->minAcl == 8)
					{
						// Supered
						$commands['super'][] = $alias;
					}
			}
			*/
			
			if(implode(', ', $commands['normal']) !== '')
			{
				$this->notice($socket, $sender, "Available Commands: " . str_replace('modules\\', '', implode(', ', $commands['normal'])));
			}
				
			$chanLevel = $that->getLevel($sender, $channel);
			$userLevel = $that->getLevel($sender, '', $that->host($data));

			echo '[INFO] chanLevel: ' . $chanLevel . PHP_EOL;
			echo '[INFO] userLevel: ' . $userLevel . PHP_EOL;

			if(implode(', ', $commands['hop']) !== '' && ($chanLevel >= 3 || $userLevel >= 7))
			{
				$this->notice($socket, $sender, "+h Commands: " . str_replace('modules\\', '', implode(', ', $commands['hop'])));
			}
			
			if(implode(', ', $commands['op']) !== '' && ($chanLevel >= 4 || $userLevel >= 7))
			{
				$this->notice($socket, $sender, "+o Commands: " . str_replace('modules\\', '', implode(', ', $commands['op'])));
			}
			
			if(implode(', ', $commands['protect']) !== '' && ($chanLevel >= 5 || $userLevel >= 7))
			{	
				$this->notice($socket, $sender, "+a Commands: " . str_replace('modules\\', '', implode(', ', $commands['protect'])));
			}
			
			if(implode(', ', $commands['owner']) !== '' && ($chanLevel >= 6 || $userLevel >= 7))
			{
				$this->notice($socket, $sender, "+q Commands: " . str_replace('modules\\', '', implode(', ', $commands['owner'])));
			}
			
			if(implode(', ', $commands['admin']) !== '' && $userLevel >= 7)
			{
				$this->notice($socket, $sender, "Admin Commands: " . str_replace('modules\\', '', implode(', ', $commands['admin'])));
			}
		
			if(implode(', ', $commands['super']) !== '' && $userLevel >= 8)
			{
				$this->notice($socket, $sender, "Super Commands: " . str_replace('modules\\', '', implode(', ', $commands['super'])));
			}
			
			$this->notice($socket, $sender, "Type '{$that->prefix}help command' to learn more about a command.");
		}
		else
		{
			$help = explode(' ', $args);
			$help = $help[0];
			if(isset($that->modules[strtolower($help)]) /*|| isset($that->modules_alias[strtolower($help)])*/)
			{
				if(isset($that->modules[strtolower($help)]->helpline) /*&& !empty($that->modules[strtolower($help)]->helpline)*/)
				{
					$this->notice($socket, $sender, "{$help}--" . $that->modules[strtolower($help)]->helpline);
				}
				/*elseif(isset($that->modules[@$that->modules_alias[strtolower($help)]]->helpline) || !empty($that->modules[@$that->modules_alias[strtolower($help)]]->helpline))
				{
					$this->notice($socket, $sender, "{$help}--" . $that->modules[$that->modules_alias[strtolower($help)]]->helpline);
				}*/
				else
				{
					$this->notice($socket, $sender, "Command {$help} does not have any help information.");
				}
			}
			else
			{
				$this->notice($socket, $sender, "Command {$help} does not exist.");
			}
		}
	}
}