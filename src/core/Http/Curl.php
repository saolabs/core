<?php

/**
 * @author Le Ngoc Doan
 * @copyright 2015
 * @update 2019
 */

namespace Saola\Core\Http;

use Saola\Core\Magic\Str;

class Curl
{
	var $headers;
	var $user_agent;
	var $compression;
	var $cookie_file;
	var $proxy;
	var $url;
	var $ipAddress = null;
	var $data_count = 0;
	var $cookies = false;
	public function __construct($url = 'http://localhost/', $cookies = false, $cookie = null, $compression = 'gzip', $proxy = '')
	{
		$this->url = $url;
		$this->headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg';
		$this->headers[] = 'Connection: Keep-Alive';
		$this->headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
		$this->user_agent = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36';
		$this->compression = $compression;
		$this->proxy = $proxy;
		$this->cookies = $cookies;
		if ($this->cookies == true) $this->cookie($cookie);
	}
	public function set_user_agent($user_agent = null)
	{
		if ($user_agent) $this->user_agent = $user_agent;
		return $this;
	}
	public function reset()
	{
		$this->headers = array();
		$this->user_agent = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36';
		$this->url = 'http://localhost/';
	}
	public function cookie($cookie_file)
	{
		if (file_exists(public_path('/cookies/' . $cookie_file))) {
			$this->cookie_file = public_path('/cookies/' . $cookie_file);
		} else {
			fopen($cookie_file, 'w') or $this->error('The cookie file could not be opened. Make sure this directory has the correct permissions');
			$this->cookie_file = $cookie_file;
			fclose($this->cookie_file);
		}
	}

	public function getheader()
	{
		$url = $this->url;
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 1);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == true) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == true) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($process, CURLOPT_CAINFO, NULL);
		curl_setopt($process, CURLOPT_CAPATH, NULL);
		$return = curl_exec($process);
		curl_close($process);
		return $return;
	}
	public function Data2String($data)
	{
		$fields = '';
		$tt = 0;
		if (is_array($data)) {
			$tt = count($data);
			foreach ($data as $key => $value) {
				$fields .= $key . '=' . urlencode($value) . "&";
			}
			rtrim($fields, "&");
		} else {
			$fields = $data;
			$tt = 1;
		}
		$this->data_count = $tt;
		return $fields;
	}
	public function get($url = null, $data = [])
	{
		$url = ($url) ? $url : $this->url;
		$fields = $this->Data2String($data);
		if (is_array($data) && count($data) > 0) $url .= (preg_match('/\?/', $url) ? "&" : "?") . $fields;
		$process = \curl_init($url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		//curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == true) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == true) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($process, CURLOPT_CAINFO, NULL);
		curl_setopt($process, CURLOPT_CAPATH, NULL);
		if ($this->ipAddress) {
			curl_setopt($process, CURLOPT_HTTPHEADER, array("REMOTE_ADDR: $this->ipAddress", "HTTP_X_FORWARDED_FOR: $this->ipAddress"));
		}


		$return = curl_exec($process);
		curl_close($process);
		//$this->reset();
		return $return;
	}
	public function post($url = null, $data = [])
	{
		$url = ($url) ? $url : $this->url;
		$fields = $this->Data2String($data);
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		//curl_setopt($process, CURLOPT_HEADER, 1);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == true) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == true) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_POST, $this->data_count);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($process, CURLOPT_CAINFO, NULL);
		curl_setopt($process, CURLOPT_CAPATH, NULL);
		if ($this->ipAddress) {
			curl_setopt($process, CURLOPT_HTTPHEADER, array("REMOTE_ADDR: $this->ipAddress", "HTTP_X_FORWARDED_FOR: $this->ipAddress"));
		}

		$return = curl_exec($process);
		curl_close($process);
		//$this->reset();
		return $return;
	}
	public function api($url = null, $method = 'GET', $data = [])
	{
		if (strtoupper($method) == 'POST') {
			$r = $this->post($url, $data);
		} else {
			$r = $this->get($url, $data);
		}
		return $r;
	}

	public function error($error)
	{
		echo "<center><div style='width:500px;border: 3px solid #FFEEFF; padding: 3px; background-color: #FFDDFF;font-family: verdana; font-size: 10px'><b>cURL Error</b><br>$error</div></center>";
		die;
	}

	public function fakeIpAddress($ip = null)
	{
		if ($ip) {
			$ippart = explode('.', $ip);
			if (count($ippart) == 4) {
				$s = true;
				foreach ($ippart as $ipn) {
					$n = Str::toNumber($ipn);
					if ($n != $ipn || $n < 0 || $n > 255) {
						$s = false;
					}
				}
				if ($s) {
					$this->ipAddress = $ip;
					return $this;
				}
			}
		}
		$this->ipAddress = implode('.', [rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255)]);
		return $this;
	}
}
