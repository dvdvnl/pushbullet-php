<?php

class Pushbullet
{
    private $accessToken;
    private $curl;

    public function __construct($accessToken, $baseUrl = 'https://api.pushbullet.com/v2/')
    {
        $this->accessToken = $accessToken;
        $this->baseUrl = $baseUrl;
    }

    /**
    * Send a push
    * https://docs.pushbullet.com/#create-push
    */
    private function _push($target, $type, array $args)
    {
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

                // Upload request (https://docs.pushbullet.com/#upload-request)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileType = finfo_file($finfo, $filePath);
                $uploadRequest = json_decode($this->_sendRequest('upload-request', [
                    'file_name' => $fileName,
                    'file_type' => $fileType
                ]));

				// Upload file (https://docs.pushbullet.com/#upload)
                $cFile = curl_file_create($filePath);

                $post = [
                    'key' => $fileName,
                    'AWSAccessKeyId' =>  $uploadRequest->data->awsaccesskeyid,
                    'acl' => $uploadRequest->data->acl,
                    'policy' =>  $uploadRequest->data->policy,
                    'signature' => $uploadRequest->data->signature,
                    'Content-Type' =>  $fileType,
                    'file' => new CurlFile(realpath($filePath), $fileType, $fileName)
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $uploadRequest->upload_url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                $response = curl_exec($ch);

                if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 204)
					throw new Exception($response);

                curl_close($ch);

                // Data for notification
                $data['body'] = $body;
                $data['file_name'] = $fileName;
                $data['file_type'] = $fileType;
                $data['file_url'] = $uploadRequest->file_url;
                break;
        }

        // Target selection
        if (filter_var($target, FILTER_VALIDATE_EMAIL) !== false)
            $data['email'] = $target;
        elseif (substr($target, 0, 1) == '#')
            $data['channel_tag'] = substr($target, 1);
        else
            $data['device_iden'] = $target;

		// Send push
        return $this->_sendRequest('pushes', $data);
    }

	/**
	* Sends push notification
	*/
    private function _sendRequest($path, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_USERPWD, $this->accessToken);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen(json_encode($data))]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
    * Push a link
    */
    public function pushLink($target, $title, $url = null, $body = null)
    {
        return $this->_push($target, 'link', compact('title', 'url', 'body'));
    }

    /**
    * Push a note
    */
    public function pushNote($target, $title, $body = null)
    {
        return $this->_push($target, 'note', compact('title', 'body'));
    }

    /**
    * Push a file
    */
    public function pushFile($target, $filePath, $fileName, $body = null)
    {
        return $this->_push($target, 'file', compact('filePath', 'fileName', 'body'));
    }
}
