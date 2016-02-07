<?

class Pushbullet {

	private $accessToken;
	private $curl;

	public function __construct($accessToken){
		$this->accessToken = $accessToken;
		$this->curl = curl_init();
	}

	public function __destruct(){
		curl_close($this->curl);
	}

	private function _push($target, $type, array $args){
		extract($args);

		$data['type'] = $type;
		switch ($type) {
			case 'note':
				$data['title'] = $title;
				$data['body'] = $body;
				break;
			case 'link':
				$data['title'] = $title;
				$data['url'] = $url;
				$data['body'] = $body;
				break;
			case 'address':
				break;
			case 'list':
				break;
			case 'file':
				break;
		}

		if(filter_var($target, FILTER_VALIDATE_EMAIL) !== false){
			$data['email'] = $target;
		}else if (substr($target, 0, 1) == '#'){
			$data['channel_tag'] = substr($target, 1);
		}else{
			$data['device_iden'] = $target;
		}
		$dataString = json_encode($data);

		curl_setopt($this->curl, CURLOPT_URL, 'https://api.pushbullet.com/v2/pushes');
		curl_setopt($this->curl, CURLOPT_USERPWD, $this->accessToken);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($dataString)]);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $dataString);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_exec($this->curl);
	}

	public function pushLink($target, $title, $url = null, $body = null){
		return $this->_push($target, 'link', compact('title', 'url', 'body'));
	}

	public function pushNote($target, $title, $body = null){
		return $this->_push($target, 'note', compact('title', 'body'));
	}

}
