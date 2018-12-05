<?php
//namespace PLibrary;
/**
 * 
 * @author dem
 *
 */
class TService {

	/**
	 *
	 * @var type
	 */
	public static $instance = NULL;

	/**
	 * 实例化类
	 * @return type
	 */
	public static function instance() {
		if(self::$instance == NULL) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function requestService($url, $data = null, $simaple = false,$is_json=false)
	{

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	 	if (!empty($data)&&$is_json==false){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		}else{
		    curl_setopt($curl, CURLOPT_POST, 1);
		    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		} 

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$error = curl_error($curl);
		curl_close($curl);
		if($simaple) {
 			$response = array('status' => 0, 'error' => '', 'code' => '0', 'msg' => '', $data = array());
			if($httpCode == 200) {
			    //echo 999;exit;
				$result = json_decode($output, true);
				if($result) {
					$response['status'] = 1;
					$response['code'] = $result['code'];
					$response['msg'] = $result['msg'];
					$response['data'] = $result['data'];
				} else {
					$response['error'] = $output;
				}
			} else {
				$response['error'] = $error;
			}
			return $response;
		} else {
			return array(
				'httpCode' => $httpCode,
				'error' => $error,
				'output' => $output,
				'data' => json_decode($output, true),
			);
		}
	}
	
	

}
