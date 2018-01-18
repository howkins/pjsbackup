<?php

namespace howkins\pjsbackup;
use ZipArchive;

class Backup {
	
	public $hasConnection = false;
	public $connection;
	public $remotePath;
	public $localPath;
	public $filter = [];

	public function __construct(){
	}

	public function location($localPath,$remotePath){
		$this->localPath = $this->realPath($localPath);
		$this->remotePath = $this->realPath($remotePath);	
		return $this;
	}

	public function connect($type, $options){
		if($type == 'sftp'){
			$hostname = data_get($options, 'host');
			$user = data_get($options, 'username');
			$password = data_get($options, 'password');
			$port = data_get($options, 'port');

			$connection = ssh2_connect($hostname, $port);
			ssh2_auth_password($connection, $user, $password);
			$this->connection = ssh2_sftp($connection);
		}

		if (!$this->connection){
			throw new \Exception('Could not initialize connection.');
		}
		$this->hasConnection = true;
		return $this;
	}

	public function collect($collection){
		foreach($collection as $path){
			if( file_exists( $this->localPath.$path ) ){
				$this->filter[] = $this->localPath.$path;
			}
			$path_ = $this->localPath.$this->realPath($path);
			if(is_dir($path_)){
				$this->filter[] = $path_;
			}
		}
		return $this;
	}
	
	public function dump(){
		echo "Start backup\n";
		$this->rcopy($this->localPath, $this->remotePath);		
		echo "Finish backup\n";
	}

	private function isActiveFilterPath($src){
		if(empty($this->filter)){
			return true;
		}
		foreach ($this->filter as $fltr) {
			if(starts_with($src,  $fltr) ){
				return true;
			}
		}
		return false;
	}

	private function rcopy($src, $dst){
		if (is_dir( $src )) {
			if(!$this->remoteHasDir($dst)){
				$this->mkdir( $dst, 0777 );
				echo "Create directory to $dst \n";
			}
			$files = $this->scandir( $src );
			foreach ( $files as $file ){
				if ($file != "." && $file != ".."){			
					$src = $this->realPath($src);
					$dst = $this->realPath($dst);
					$this->rcopy("$src$file", "$dst$file");
				}
			}
		}else if(file_exists($src)){
			if($this->isActiveFilterPath($src)){
				if($this->copy ( $src, $dst )){
					echo "Copy $src to $dst \n";
					unlink($src);
					echo "Unlink $src \n";
				}
			}
		}
		if (is_dir( $dst )) {
			if ((int)$this->is_dir_empty($dst)) {
				$this->rmdir ($dst);
				echo "Remove directory because is empty $dst \n";
			}
		}
	}
	
	private function remoteHasDir($path){
		if($this->hasConnection){
			return file_exists('ssh2.sftp://' . $this->connection . $path);
		}else{
			return is_dir($path);
		}
	}
	private function is_dir_empty($dir) {
	  if (!is_readable($dir)) return NULL; 
	  return (count(scandir($dir)) == 2);
	}

	private function rmdir($dir, $local = false){
		if($this->hasConnection && $local == false){
			ssh2_sftp_rmdir($this->connection, $dir);
		}else{
			rmdir ($dir);
		}
	}
	private function mkdir($dirname, $mode = 0777, $recursive = false){
		if($this->hasConnection){
			return ssh2_sftp_mkdir ( $this->connection , $dirname, $mode, $recursive );		
		}
		return mkdir($dirname, $mode, $recursive);
	}

	private function copyViaSftp($src, $dst){
		$sftp = $this->connection;
		$stream = @fopen("ssh2.sftp://$sftp/$dst", 'w');
		if (! $stream){		
      throw new \Exception("Could not open file: $dst");
		}
		$data_to_send = @file_get_contents($src);
		if ($data_to_send === false){		
      throw new \Exception("Could not open local file: $src.");
		}
		if (@fwrite($stream, $data_to_send) === false){		
      throw new \Exception("Could not send data from file: $src.");
		}
		@fclose($stream);
		return true;
	}

	private function scandir($path, $remotely = false){
		if($this->hasConnection && $remotely){
			$sftp_fd = intval($this->connection);
			$handle = opendir("ssh2.sftp://$sftp_fd/$path");
			$list = [];
	    while (false != ($entry = readdir($handle))){
	      $list[] = $entry;
	    }
	    if(empty($list)){
	    	throw new \Exception("Could not find the path.");	
	    }
	    return $list;
		}
    return scandir ( $path );
	}

	private function rm($file){
		unlink($file);
	}

	private function copy($src, $dst){
		if($this->hasConnection){
			return $this->copyViaSftp($src, $dst);
		}else{
			return copy ( $src, $dst );
		}
	}
	private function realPath($path){
		return preg_replace("/(\w)$/", '$1/', $path);
	}
}



