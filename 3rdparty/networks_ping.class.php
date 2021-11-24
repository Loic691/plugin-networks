<?php
class networks_Ping {
	private $host;
	private $ttl;
	private $port = 80;
	private $data = 'Ping';
	private $ifName = 'ens0';
    private $mac = '00:00:00:00:00:00:00:00';

	public function __construct($host, $ttl = 255) {
		if (!isset($host)) {
			throw new \Exception("Error: Host name not supplied.");
		}
		$this->host = $host;
		$this->ttl = $ttl;
	}

	public function setTtl($ttl) {
		$this->ttl = $ttl;
	}

	public function getTtl() {
		return $this->ttl;
	}

	public function setHost($host) {
		$this->host = $host;
	}
  
   public function setMac($mac) {
		$this->mac = $mac;
	}

	public function getHost() {
		return $this->host;
	}
	
	public function setIfName($ifName) {
		$this->ifName = $ifName;
	}

	public function setPort($port) {
		$this->port = $port;
	}

	public function getPort() {
		return $this->port;
	}

	public function ping($method = 'ip') {
		switch ($method) {
			case 'ip':
				return $this->pingExec('ip');
			case 'arp':
				return $this->pingMac();
			case 'port':
				return $this->pingPort();
		}
		return false;
	}

	private function pingExec($_mode = 'ip') {
		$latency = false;
		$ttl = escapeshellcmd($this->ttl);
		$host = escapeshellcmd($this->host);
		$exec_string = 'sudo ping -n -c 1 -t ' . $ttl . ' ' . $host . ' 2> /dev/null';
		exec($exec_string, $output, $return);
		$output = array_values(array_filter($output));
		if (!empty($output[1])) {
			if (count($output) >= 5) {
				$response = preg_match("/time(?:=|<)(?<time>[\.0-9]+)(?:|\s)ms/", $output[count($output)-4], $matches);
				if ($response > 0 && isset($matches['time'])) {
					$latency = $matches['time'];
				}				
			}			
		}
		return $latency;
	}
	
	private function pingMac() {
		$latency = false;
		$exec_string = 'sudo /usr/sbin/arp-scan -I ' . $this->ifName . ' -l -g --retry=5 -T ' . $this->mac . ' -t 800 | grep -i ' . $this->mac . ' | wc -l 2> /dev/null';
		exec($exec_string, $output, $return);
      
		if (!empty($output[0])) {
			$latency = $output[0];	
		}
        return $latency;
	}
	
	private function pingPort() {
		$start = microtime(true);
		$fp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->ttl);
		if (!$fp) {
			$latency = false;
		} else {
			$latency = microtime(true) - $start;
			$latency = round($latency * 1000);
		}
		return $latency;
	}

}
?>