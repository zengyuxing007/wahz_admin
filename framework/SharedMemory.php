<?
class SharedMemory {

	private $shmId;

	private $readCache = NULL;
	private $lifetime = 3;

	public function __construct() {
		if (! function_exists('shmop_open')) {
			throw new Exception('not shmop');
		}
		$memoryKey = 0xff3;
		$this->shmId = shmop_open($memoryKey, "c", 0644, 1024000);
	}

	public function getinfo(){
		$array = array(
			'shmid' => $this->shmId,
			'sharddata' => $this->readFromSharedMemory()
		);
		return $array;
	}

	public function set($key, $value) {
		$data = $this->readFromSharedMemory();
		foreach($data as $k=>$v){
			if(($v['lifetime']+$this->lifetime) < time()){
				unset($data[$k]);
			}
		}
		$data[$key]['data'] = $value;
		$data[$key]['lifetime'] = time();
		return (boolean) shmop_write($this->shmId, serialize($data), 0);
	}

	public function get($key) {
		$data = $this->readFromSharedMemory();
		if (! isset($data[$key]['data'])) {
			return false;
		}
		return $data[$key]['data'];
	}

	private function readFromSharedMemory() {
		$dataSer = shmop_read($this->shmId, 0, shmop_size($this->shmId));
		$data = @unserialize($dataSer);
		if (! $data) {
			return array();
		}
		return $data;
	}
	function close(){
		@shmop_delete($this->shmId);
		@shmop_close($this->shmId);
	}
}
?>