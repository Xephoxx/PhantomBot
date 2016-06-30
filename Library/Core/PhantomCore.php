<?php

namespace Core;

$path = str_replace('\\', '/', str_replace(array('Library/Core/PhantomCore.php', 'Library\Core\PhantomCore.php'), '', __FILE__));

class PhantomCore
{
	public $socket;
	public $nick;
	public $size = 512;
	public $prefix = '%';
	public $modules;
	public $modules_regex = array();
	public $modules_prefix = array();
	public $modules_alias = array();
	public $modules_hooks = array();
	public $config;
	public $db;
	public $dbinfo;
	public $path;
	public $shmop;
	
	private function send($signal)
	{
		fputs($this->socket, Helpers\Str::trim($signal) . "\n");
		echo '[SEND] ';
		if(strstr(trim($signal), 'PASS'))
		{
			echo "PASS ****\n";
		}
		elseif(strstr(trim($signal), 'OPER'))
		{
			echo "OPER **** *****\n";
		}
		else
		{
			echo trim($signal) . "\n";
		}
	}
	
	private function privmsg($target, $message)
	{
		$this->send('PRIVMSG ' . Helpers\Str::trim($target) . ' :' . Helpers\Str::trim($message));
	}
	
	public function command($input)
	{
		$input = explode(' ', Helpers\Str::trim($input));
		$command = isset($input[0]) ? Helpers\Str::after($this->prefix, $input[0]) : '';
		return $command;
	}
	
	public function input($data)
	{
		$input = explode(':', Helpers\Str::trim($data));
		$input = isset($input[0], $input[1]) ? Helpers\Str::trim(str_replace("{$input[0]}:{$input[1]}:", '', $data)) : '';
		return $input;
	}
	
	public function sender($data)
	{
		$e = explode(' ', Helpers\Str::trim($data));
		$sender = isset($e[0]) ? explode('!', Helpers\Str::after(':', $e[0])) : array('');
		return $sender[0];
	}
	
	public function channel($data)
	{
		$e = explode(' ', Helpers\Str::trim($data));
		$chan = isset($e[2]) ? $e[2] : '';
		if (!Helpers\Str::beginsWith('#', $chan)) $chan = $this->sender($data);
		return $chan;
	}
	
	public function host($data)
	{
		$e = explode(' ', Helpers\Str::trim($data));
		$e = explode('@', $e[0]);
		$host = isset($e[1]) ? $e[1] : '';
		return $host;
	}
	
	public function getLevel($user, $channel, $host = '')
	{
		echo "GetLevel" . PHP_EOL;
		$user = trim($user);
		$host = trim($host);
		if(!empty($host))
		{
			//Override in case mysql connection is dead.
			$admins = array(
				/*Nick*/'x2Fusion' => array(
					'host' => '127.0.0.1',
					'super' => true
				)
			);
			
			if(in_array(strtolower($user), $admins))
			{
				if($admins[strtolower($user)]['host'] == strtolower($host))
				{
					return $admins[strtolower($user)]['super'] ? 8 : 7;
				}
			}
			//Otherwise, check mysql
			/*try
			{
				$admin = $this->db->prepare('SELECT `super` FROM `admins` WHERE `nick` = :nick AND `host` = :host');
				$admin->bindParam(':nick', strtolower($user), PDO::PARAM_STR);
				$admin->bindParam(':host', strtolower($host), PDO::PARAM_STR);
				$admin->execute();
				if($admin->rowCount() > 0)
				{
					$super = $admin->fetch();
					if($super->super == true)
					{
						return 8;
					}
					else
					{
						return 7;
					}
				}
			}
			catch (PDOException $e)
			{
				$this->logE($e);
			}
			*/
		}
		if(!empty($channel))
		{
			$this->send('WHOIS ' . $user);
			$gotit = false;
			while (!$gotit)
			{
				$data = $this->listen();
				$channelflag = $this->expect($data, 'whoischannel', array('who' => $user, 'channel' => $channel));
				$end = $this->expect($data, 'endofwhois', array('who' => $user));
				if ($channelflag !== false)
				{
					$gotit = true;
					switch ($channelflag[2])
					{
						case '~':
							return 6;
						break;
							
						case '&':
							return 5;
						break;
							
						case '@':
							return 4;
						break;
						
						case '%':
							return 3;
						break;
						
						case '+':
							return 2;
						break;
							
						default:
							return 1;
						break;
					}
				}
				elseif($end !== false)
				{
					$gotit = true;
					return 1;
				}
				else
				{
					$this->process($data);
				}
			}
		}
		return 1;
	}
	
	public function __construct($server, $port, $nickname, &$shmop, $config = array())
	{
		global $path;
		$this->path = $path;
		$ssl = Helpers\Str::beginsWith('+', $port);
		if($ssl)
		{
			$server = 'ssl://' . $server;
			$port = Helpers\Str::after('+', $port);
		}
		$this->nick = Helpers\Str::trim($nickname);
		if(isset($config['server']['prefix']))
		{
			$this->prefix = Helpers\Str::trim($config['server']['prefix']);
		}
		$this->config = $config;
		$this->shmop = $shmop;
		
		$this->socket = fsockopen($server, $port);
		if($this->socket)
		{
			if(isset($config['server']['password']) && strlen($config['server']['password']))
			{
				$this->send("PASS {$config['server']['password']}");
			}
			$this->send("NICK {$this->nick}");
			$ident = isset($config['server']['ident']) ? $config['server']['ident'] : $this->nick;
			$this->send("USER {$ident} * * :Phantom Bot");
			$pinged = false;
			$nickserv = !isset($config['server']['nickserv'])?false:true;
			$joined = false;
			$count = 0;
			
			while(!$pinged)
			{
				$data = fgets($this->socket, $this->size);
				echo '[RECV] ' . trim($data) . "\n";
				
				if(preg_match("/:Nickname is already in use.$/", Helpers\Str::trim($data)))
				{
					die('Nickname not available.');
				}
				
				if($count == 10)
				{
					break;
				}
				
				if(Helpers\Str::beginsWith('PING :', $data))
				{
					$ping = Helpers\Str::after('PING :', $data);
					$this->send('PONG :' . $ping);
					$pinged = true;
				}
				
				$count++;
			}
			
			while(!$nickserv)
			{
				$data = fgets($this->socket, $this->size);
				echo '[RECV] ' . trim($data) . "\n";
				
				if(preg_match("/^\:NickServ\!NickServ@.* NOTICE {$this->nick} :This nickname is registered./i", Helpers\Str::trim($data)))
				{
					$this->privmsg('NickServ', "identify {$config['server']['nickserv']}");
					$nickserv = true;
				}
			}

			while(!$joined)
			{
				$data = fgets($this->socket, $this->size);
				echo '[RECV] ' . trim($data) . "\n";
				
				foreach($config['server']['channels'] as $channel)
				{
					@list($channel, $password) = explode(':', $channel);
					if(Helpers\Str::beginsWith('#', $channel))
					{
						$this->send('JOIN ' . $channel . ' ' . $password);
						usleep(250000);
					}
				}
				$joined = true;
			}
		}
	}
	
	public function load($modules)
	{
		$allowed_hooks = array(
			'beforeCommand',
			'beforePrefix',
		);
		if(is_array($modules))
		{
			foreach($modules as $module)
			{
				$module = strtolower($module);
				echo $module . PHP_EOL;
				require_once($this->path . 'Library/Module/' . $module . '.module.php');
				$class = ucfirst($module);
				$this->modules[$module] = new $class($this);
				echo "[INFO] Loaded: {$class}\n";
				
				if(!empty($this->modules[$module]->regex))
				{
					$this->modules_regex[$module] = $this->modules[$module]->regex;
				}
				
				if (!empty($this->modules[$module]->prefix))
				{
					$this->modules_prefix[$module] = $this->modules[$module]->prefix;
				}
				
				if(!empty($this->modules[$module]->alias) && is_array($this->modules[$module]->alias))
				{
					foreach ($this->modules[$module]->alias as $alias)
					{
						$this->modules_alias[$alias] = $module;
					}
				}
				
				if(!empty($this->modules[$module]->hooks) && is_array($this->modules[$module]->hooks))
				{
					foreach($this->modules[$module]->hooks as $hook)
					{
						if(in_array($hook, $allowed_hooks))
						{
							$this->modules_hooks[$hook][] = array('hook' => $hook, 'module' => $module);
						}
					}
				}
				else
				{
					echo "{$module}'s hook is not supported.";
				}
			}
		}
		elseif(is_bool($modules) && $modules === true)
		{
			foreach(glob($this->path . 'Library/Module/*.module.php') as $module)
			{
				require_once($module);
				$module = strtolower(Helpers\Str::trim(str_replace('.module.php', '', basename($module))));
				$class = ucfirst($module);
				$this->modules[$module] = new $class($this);
				if($module !== 'base')
				{
					echo "[INFO] Loaded: {$class}\n";
					if(!empty($this->modules[$module]->regex))
					{
						$this->modules_regex[$module] = $this->modules[$module]->regex;
					}
					
					if(!empty($this->modules[$module]->prefix))
					{
						$this->modules_prefix[$module] = $this->modules[$module]->prefix;
					}
					
					if(!empty($this->modules[$module]->alias) && is_array($this->modules[$module]->alias))
					{
						foreach($this->modules[$module]->alias as $alias)
						{
							$this->modules_alias[$alias] = $module;
						}
					}
					
					if(!empty($this->modules[$module]->hooks) && is_array($this->modules[$module]->hooks))
					{
						foreach ($this->modules[$module]->hooks as $hook)
						{
							if(in_array($hook, $allowed_hooks))
							{
								$this->modules_hooks[$hook][] = array('hook' => $hook, 'module' => $module);
							}
							else
							{
								echo "[INFO] {$module}'s hook is not supported.";
							}
						}
					}
				}
			}
		}
		elseif(file_exists($this->path . 'Library/Module/' . strtolower($modules) . '.module.php'))
		{
			$module = strtolower($modules);
			echo $module . PHP_EOL;
			require_once($this->path . 'Library/Module/' . $module . '.module.php');
			$class = ucfirst($module);
			$this->modules[$module] = new $class($this);
			echo "[INFO]Loaded: {$class}\n";
			
			if(!empty($this->modules[$module]->regex))
			{
				$this->modules_regex[$module] = $this->modules[$module]->regex;
			}
			
			if(!empty($this->modules[$module]->prefix))
			{
				$this->modules_prefix[$module] = $this->modules[$module]->prefix;
			}
			
			if(!empty($this->modules[$module]->alias) && is_array($this->modules[$module]->alias))
			{
				foreach ($this->modules[$module]->alias as $alias)
					$this->modules_alias[$alias] = $module;
			}
			
			if(!empty($this->modules[$module]->hooks) && is_array($this->modules[$module]->hooks))
			{
				foreach ($this->modules[$module]->hooks as $hook)
				{
					if(in_array($hook, $allowed_hooks))
					{
						$this->modules_hooks[$hook][] = array('hook' => $hook, 'module' => $module);
					}
					else
					{
						echo "[INFO] {$module}'s hook is not supported.";
					}
				}
			}
		}
	}
	
	public function expect($data, $what, $with)
	{
		switch($what)
		{
			case 'nick':
				$who = preg_quote($with['who'], '/');
				if(preg_match("/{$who}![a-zA-Z0-9~]+@.+ NICK :(.+)/i", $data, $matches))
				{
					return $matches;
				}
				else
				{
					return false;
				}
			break;
			
			case 'nickserv':
				$who = preg_quote($with['who'], '/');
				if (preg_match("/:[a-zA-Z0-9\.]+ 307 {$this->nick} ({$who}) :is a registered nick/i", $data, $matches) || preg_match("/:[a-zA-Z0-9\.]+ 330 {$this->nick} ({$who}) .*?:is logged.*?/i", $data, $matches))
				{
					return $matches;
				}
				else
				{
					return false;
				}
			break;
			
			case 'endofwhois':
				$who = preg_quote($with['who'], '/');
				if (preg_match("/:[a-zA-Z0-9\.]+ 318 {$this->nick} {$who} :End of \/WHOIS list./i", $data, $matches))
				{
					return $matches;
				}
				else
				{
					return false;
				}
			break;
			
			case 'nosuchnickchannel':
				$who = preg_quote($with['who'], '/');
				if (preg_match("/:[a-zA-Z0-9\.]+ 401 {$this->nick} {$who} :No such nick\/channel/i", $data, $matches))
				{
					return $matches;
				}
				else
				{
					return false;
				}
			break;
			
			case 'whoischannel':
				$who = preg_quote($with['who'], '/');
				$channel = preg_quote($with['channel'], '/');
				if(preg_match("/:[a-zA-Z0-9\.]+ 319 {$this->nick} ({$who}) :.*?([~&@%\+]*){$channel}[\s|$]/i", $data, $matches))
				{
					return $matches;
				}
				else
				{
					return false;
				}
			break;
			
			case 'modechannel':
				$channel = preg_quote($with['channel'], '/');
				if(preg_match("/:[a-zA-Z0-9\.]+ 324 {$this->nick} {$channel} ([\+|-][a-zA-Z]+)/i", $data, $matches))
				{
					return $matches;
				}
				else
				{
					return false;
				}
			break;
			
			case 'topicchange':
				$channel = preg_quote($with['channel'], '/');
				return preg_match("/![a-zA-Z0-9~]+@.+ TOPIC {$channel}/i", $data);
			break;
			
			default:
				return false;
			break;
		}
	}
	
	public function isConnected()
	{
		return is_resource($this->socket) && !feof($this->socket);
	}
	
	public function listen()
	{
		if(!$this->isConnected())
		{
			die("\n\nReached end of socket.\n");
		}
		$data = fgets($this->socket, $this->size);
		echo "[RECV] $data";
		return $data;
	}
	
	public function process($data)
	{
		$input = $this->input($data);
		if(!is_resource($this->db))
		{
			// DB Stuff
		}
		
		if(Helpers\Str::beginsWith('PING :', $data))
		{
			$ping = Helpers\Str::after('PING :', $data);
			$this->send('PONG :' . $ping);
			return;
		}
		
		if(isset($this->config['joinOnInvite']) && $this->config['joinOnInvite'] === true)
		{
			if(preg_match("/.*INVITE {$this->nick} :(#[#a-zA-Z0-9]+)/", $data, $match))
			{
				/*try
				{
					$channel = $this->db->prepare('INSERT INTO `channels` (`name`) VALUES (:channel)');
					$channel->bindParam(':channel', $match[1], PDO::PARAM_STR);
					$channel->execute();
				}
				catch (PDOException $e)
				{
					$this->logE($e);
				}
				$this->send('JOIN ' . $match[1]);
				*/
				return;
			}
		}
		
		foreach($this->modules_regex as $class => $regex)
		{
			if(preg_match($regex, $data, $matches))
			{
				$this->modules[$class]->match($this, $this->socket, Helpers\Str::trim($data), $matches);
			}
		}
		
		foreach($this->modules_prefix as $class => $prefix)
		{
			if(Helpers\Str::beginsWith($prefix, $input))
			{
				$command = explode(' ', Helpers\Str::after($this->prefix, $input));
				$command = $command[0];
				$pinput = explode(' ', $input);
				unset($pinput[0]);
				$pinput = implode(' ', $pinput);
				$okay = true;
				if(isset($this->modules_hooks['beforePrefix']))
				{
					foreach($this->modules_hooks['beforePrefix'] as $hook)
					{
						if($hook['hook'] == 'beforePrefix')
						{
							$okay = $this->modules[$hook['module']]->beforePrefix($this, $this->socket, Helpers\Str::trim($data), $input, $command, $pinput);
							if($okay == false)
							{
								break;
							}
						}
					}
				}
				if($okay)
				{
					$this->modules[$class]->prefix($this, $this->socket, Helpers\Str::trim($data), $input, $command, $pinput);
				}
			}
		}
		
		if(Helpers\Str::beginsWith($this->prefix, $input))
		{
			$command = strtolower($this->command($input));
			if(!($command == 'module') && (isset($this->modules[$command]) || (isset($this->modules_alias[$command]) && isset($this->modules[$this->modules_alias[$command]]))))
			{
				/*
				 LEVELS:
				 1 - regular
				 2 - voiced
				 3 - halfop
				 4 - op
				 5 - protected
				 6 - owner
				 7 - admin
				 8 - super admin
				*/
				$okay = true;
				if(isset($this->modules_hooks['beforeCommand']))
				{
					foreach($this->modules_hooks['beforeCommand'] as $hook)
					{
						if($hook['hook'] == 'beforeCommand')
						{
							$okay = $this->modules[$hook['module']]->beforeCommand($this, $this->socket, Helpers\Str::trim($data), $input, $command, Helpers\Str::after($this->prefix . $this->command($input), $input));
							if($okay == false)
							{
								break;
							}
						}
					}
				}
				if($okay)
				{
					if(isset($this->modules[$command]))
					{
						$this->modules[$command]->process($this, $this->socket, Helpers\Str::trim($data), $input, $command, Helpers\Str::after($this->prefix . $this->command($input), $input));
					}
					elseif(isset($this->modules_alias[$command]) && isset($this->modules[$this->modules_alias[$command]]))
					{
						$this->modules[$this->modules_alias[$command]]->process($this, $this->socket, Helpers\Str::trim($data), $input, $command, Helpers\Str::after($this->prefix . $this->command($input), $input));
					}
				}
			}
		}
	}
	
	public function disconnect($message = 'Quit command issued.')
	{
		$this->send('QUIT :' . Helpers\Str::trim($message));
		fclose($this->socket);
	}
}