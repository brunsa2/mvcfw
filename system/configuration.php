<?php

class Configuration {
	private $configuration;
	
	public function __construct() {
		$this->configuration = new stdClass();
		
		if($configurationDirectoryHandle = opendir(ROOT_DIRECTORY . DS . CONFIG_DIRECTORY)) {
			while(($configurationFile = readdir($configurationDirectoryHandle)) !== false) {
				self::fileIsValidConfigurationFile($configurationFile)
						and self::loadConfigurationFile(ROOT_DIRECTORY . DS . CONFIG_DIRECTORY . DS . $configurationFile);
			}
			
			closedir($configurationDirectoryHandle);
			
			return;
		}
		
		// TODO: Error handling
	}
	
	public function getConfiguration($configurationName = '') {
		return $this->configuration->$configurationName;
		// TODO: Make this function handle dot notation for subclassing, possibly also index notation, count function
	}
	
	private static function fileIsValidConfigurationFile($filename) {
		return substr($filename, strlen($filename) - 5) == '.json';
	}
	
	private function loadConfigurationFile($configurationFile) {
		$configurationFile = file_get_contents($configurationFile);
		$configuration = json_decode($configurationFile);
		$this->mergeConfiguration($configuration);
	}
	
	private function mergeConfiguration($configuration) {
		// TODO: Make this method recursively merge (array rather than stdClass?)
		foreach($configuration as $key => $value) {
			$this->configuration->$key = $value;
		}
	}
}

?>