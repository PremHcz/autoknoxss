<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class KnoxssRequest
{
	const KNOXSS_URL = 'https://knoxss.me/pro';
	const USER_AGENT = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0';
	
	public $timeout = 20;
	public $cookies = '';
	public $cookie_file = '';
	public $wpnonce = '';
	public $addon = 1;
	public $auth = '';
	
	public $target = '';
	public $post = '';
	
	public $result;
	public $result_code;
	
	
	public function __construct()
	{
		$this->cookie_file = tempnam( '/tmp', 'cook_' );
	}

	
	public function getCookies() {
		return $this->cookies;
	}
	public function setCookies( $v ) {
		$this->cookies = trim( $v );
		return true;
	}
	
	
	public function getWPnonce() {
		return $this->wpnonce;
	}
	public function setWPnonce( $v ) {
		$this->wpnonce = trim( $v );
		return true;
	}
	
	
	public function _urlencode( $str )
	{
		return str_replace( '&', '%26', $str );
	}

	
	public function go()
	{
		echo 'Testing: '.$this->target."\n";
		$this->target = str_replace( '&', '%26', $this->target );
		$post = 'target='.$this->_urlencode($this->target).'&_wpnonce='.$this->wpnonce.'&addon='.$this->addon.'&auth='.$this->auth;
		if( strlen($this->post) ) {
			$post .= '&post='.$this->_urlencode($this->post);
		}
		echo 'With post: '.$post."\n";
		
		$c = curl_init();
		curl_setopt( $c, CURLOPT_URL, self::KNOXSS_URL );
		curl_setopt( $c, CURLOPT_TIMEOUT, $this->timeout );
		curl_setopt( $c, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $c, CURLOPT_USERAGENT, self::USER_AGENT );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $c, CURLOPT_COOKIE, $this->cookies );
		curl_setopt( $c, CURLOPT_POST, true );
		curl_setopt( $c, CURLOPT_POSTFIELDS, $post );
		$this->result = curl_exec( $c );
		//var_dump($this->result);
		$this->result_code = curl_getinfo( $c, CURLINFO_HTTP_CODE );;
		
		return $this->result_code;
	}
	
	/*
	public function getCookies()
	{
		$c = curl_init();
		curl_setopt( $c, CURLOPT_URL, self::KNOXSS_URL );
		curl_setopt( $c, CURLOPT_TIMEOUT, $this->timeout );
		curl_setopt( $c, CURLOPT_CUSTOMREQUEST, 'GET' );
		curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $c, CURLOPT_USERAGENT, self::USER_AGENT );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		//curl_setopt( $c, CURLOPT_COOKIE, $this->cookies );
		curl_setopt( $c, CURLOPT_COOKIEJAR, $this->cookie_file );
		curl_setopt( $c, CURLOPT_COOKIEFILE, $this->cookie_file );
		$result = curl_exec( $c );
		//var_dump($result);
		
		$m = preg_match( '#<input type="hidden" name="_wpnonce" value="(.*)">#', $result, $matches );
		//var_dump( $matches );
		if( !$m ) {
			return false;
		} else {
			return $matches[1];
		}
	}
	*/
	
	public function getNonce()
	{
		$c = curl_init();
		curl_setopt( $c, CURLOPT_URL, self::KNOXSS_URL );
		curl_setopt( $c, CURLOPT_TIMEOUT, $this->timeout );
		curl_setopt( $c, CURLOPT_CUSTOMREQUEST, 'GET' );
		curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $c, CURLOPT_USERAGENT, self::USER_AGENT );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $c, CURLOPT_COOKIE, $this->cookies );
		//curl_setopt( $c, CURLOPT_COOKIEJAR, $this->cookie_file );
		//curl_setopt( $c, CURLOPT_COOKIEFILE, $this->cookie_file );
		$result = curl_exec( $c );
		//var_dump($result);
		
		$m = preg_match( '#<input type="hidden" name="_wpnonce" value="(.*)">#', $result, $matches );
		//var_dump( $matches );
		if( !$m ) {
			$this->wpnonce = false;
		} else {
			$this->wpnonce = $matches[1];
		}
		
		return $this->wpnonce;
	}
	
	
	public function result()
	{
		//$this->result = file_get_contents( dirname(__FILE__).'/r' );
		//var_dump( $this->result );
		
		if( $this->result_code != 200 ) {
			Utils::_println( "Error contacting KNOXSS! (".$this->result_code.")", 'yellow' );
			return 1;
		}
		
		$r = preg_match( "#<script>window.open\('(.*)', '', 'top=380, left=870, width=400, height=250'\);</script>#", $this->result, $matches );
		//var_dump( $matches );
		if( $r ) {
			Utils::_print( 'XSS found: ', 'red' );
			echo $matches[1]."\n";
			return 0;
		}

		$r = preg_match( "#No XSS found by KNOXSS#", $this->result );
		if( $r ) {
			Utils::_println( 'Looks safe.', 'green' );
			return 0;
		}

		$r = preg_match( "#network issues#", $this->result );
		if( $r ) {
			Utils::_println( 'Cannot contact target!', 'orange' );
			return 1;
		}
		
		Utils::_println( 'Cannot interpret result!', 'light_purple' );
		return 1;
		
		return $r;
	}
}
