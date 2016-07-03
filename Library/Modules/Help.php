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
					if($that->modules[$key]->minAcl <= 2)
					{
						// Normal
						$commands['normal'][] = $module;
					}
					elseif($that->modules[$key]->minAcl == 3)
					{
						// Hoped
						$commands['hop'][] = $module;
					}
					elseif($that->modules[$key]->minAcl == 4)
					{
						// Oped
						$commands['op'][] = $module;
					}
					elseif($that->modules[$key]->minAcl == 5)
					{
						// Protected
						$commands['protect'][] = $module;
					}
					elseif($that->modules[$key]->minAcl == 6)
					{
						// Ownered
						$commands['owner'][] = $module;
					}
					elseif($that->modules[$key]->minAcl == 7)
					{
						// Admined
						$commands['admin'][] = $module;
					}
					elseif($that->modules[$key]->minAcl == 8)
					{
						// SuperAdmined
						$commands['super'][] = $module;
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
			
			if(implode(', ', $commands['hop']) !== '')
			{
				$this->notice($socket, $sender, "+h Commands: " . str_replace('modules\\', '', implode(', ', $commands['hop'])));
			}
			
			if(implode(', ', $commands['op']) !== '')
			{
				$this->notice($socket, $sender, "+o Commands: " . str_replace('modules\\', '', implode(', ', $commands['op'])));
			}
			
			if(implode(', ', $commands['protect']) !== '')
			{		
				$this->notice($socket, $sender, "+a Commands: " . str_replace('modules\\', '', implode(', ', $commands['protect'])));
			}
			
			if(implode(', ', $commands['owner']) !== '')
			{
				$this->notice($socket, $sender, "+q Commands: " . str_replace('modules\\', '', implode(', ', $commands['owner'])));
			}
			
			if(implode(', ', $commands['admin']) !== '')
			{
				$this->notice($socket, $sender, "Admin Commands: " . str_replace('modules\\', '', implode(', ', $commands['admin'])));
			}
		
			if(implode(', ', $commands['super']) !== '')
			{
				$this->notice($socket, $sender, "Super Commands: " . str_replace('modules\\', '', implode(', ', $commands['super'])));
			}
			
			$this->notice($socket, $sender, "Type '{$that->prefix}help command' to learn more about a command.");
		}
		else
		{
			$help = explode(' ', $args);
			$help = $help[0];
			if(isset($that->modules[strtolower($help)]) || isset($that->modules_alias[strtolower($help)]))
			{
				if(isset($that->modules[strtolower($help)]->helpline) && !empty($that->modules[strtolower($help)]->helpline))
				{
					$this->notice($socket, $sender, "{$help}--" . $that->modules[strtolower($help)]->helpline);
				}
				elseif(isset($that->modules[@$that->modules_alias[strtolower($help)]]->helpline) || !empty($that->modules[@$that->modules_alias[strtolower($help)]]->helpline))
				{
					$this->notice($socket, $sender, "{$help}--" . $that->modules[$that->modules_alias[strtolower($help)]]->helpline);
				}
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