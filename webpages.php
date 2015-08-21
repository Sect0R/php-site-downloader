<?php

class WebPages
{

	var $Referer = false;
	var $Header = 0;
	var $FollowLocation = 1;
	var $CookieFile = false;
	var $Timeout = 5;
	var $UserAgent = '';
	var $showError = true;

	function __construct( $Timeout = false, $CookieFile = false )
	{
        if ( $CookieFile )
            $this->CookieFile = $CookieFile;

		$UserAgent = array(
			'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 6.0)',
			'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.0.04506.30)',
			'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; FDM; .NET CLR 1.1.4322)',
			'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.40607)',
			'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322)',
			'Opera/9.60 (Windows NT 6.0; U; ru) Presto/2.1.1',
			'Opera/9.61 (Windows NT 5.1; U; ru) Presto/2.1.1',
			'Opera/9.50 (Windows NT 5.1; U; ru)',
			'Opera/9.51 (Windows NT 5.1; U; ru)',
			'Opera/9.52 (Windows NT 5.1; U; ru)',
			'Opera/9.10 (Windows NT 5.1; U; ru)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 1.1.4322; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; GTB6)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; GTB6; .NET CLR 1.1.4322)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; GTB6; .NET CLR 2.0.50727)',
		);
		
		$this->UserAgent  = $UserAgent[ array_rand( $UserAgent ) ];
		
		if ( $Timeout )
			$this->Timeout = $Timeout;
	}
	
/*======================================================================
    Function:	GetPage
    Purpose:	Getting page from URL
	Var:		$url - Page url
	Author:		Odinets Ruslan
======================================================================*/
	function GetPage($url, $postarray = false, $NotEncode = false)
	{
		if ( $this->CookieFile && !file_exists($this->CookieFile) )
		{
			touch($this->CookieFile);
			@chmod( $this->CookieFile, 0755);
		}
		
		$Accept 	= "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" ;

		if ( !$this->Referer )	$this->Referer = $url;
		
		if (function_exists('curl_init')) 
		{
			$Header = array();
			$Header [] = 'Accept: : ' . $Accept;
			$Header [] = "Accept_charset: windows-1251,utf-8;q=0.7,*;q=0.7";
			$Header [] = "Accept_encoding: identity";
			$Header [] = "Accept_language: ru,en-us;q=0.7,en;q=0.3";
			$Header [] = "Connection: keep-alive";
			$Header [] = "Cache-Control: max-age=0";
			$Header [] = "Keep_alive: 300";

			$ch = curl_init($url);
			
			if ( $postarray )
			{
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postarray );
			}
			
			if ( $this->CookieFile )
			{
				curl_setopt($ch, CURLOPT_COOKIEFILE, $this->CookieFile);
				curl_setopt($ch, CURLOPT_COOKIEJAR, $this->CookieFile);
			}
			
				curl_setopt($ch, CURLOPT_HEADER, $this->Header);
				curl_setopt($ch, CURLOPT_REFERER, $this->Referer);
				curl_setopt($ch, CURLOPT_USERAGENT, $this->UserAgent);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_TIMEOUT, $this->Timeout);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->Timeout);
			   @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->FollowLocation);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $Header);

			$buf = curl_exec ($ch);

			if ( curl_error($ch) && $this->showError )
				echo curl_error($ch);
		
			curl_close ($ch);
		}
		elseif (function_exists('file_get_contents')) 
		{
			$buf = @file_get_contents($url);
		}
		elseif (function_exists('fsockopen'))
		{
			$url_parts = parse_url($url);
			$host = $url_parts['host'];
			$port = @$url_parts['port'];
			if (empty($url_parts['query'])) $url_parts['query'] = '';
				if (!$port) $port = 80;
			
			$uri = $url_parts['path'] . (!empty($url_parts['query'])?'?'.$url_parts['query']:'');

			$headers  = "GET $uri HTTP/1.0\r\n";
			$headers .= "Host: $host:$port\r\n";
			$headers .= 'User-Agent: ' . $this->UserAgent . "\r\n";
			$headers .= 'Accept: ' . $Accept . "\r\n";

			$socket = fsockopen('tcp://' . $host, $port, $errno, $errstr, $this->Timeout);
			fwrite($socket, $headers . "\r\n");
			$output = '';
				while(!@feof($socket)) {
					$buffer = @fgets($socket, 8192);
					$output .= $buffer;
				}

			fclose($socket);
			$buf = $output;

		}
		elseif (function_exists('fopen'))
		{
			$buf = $this->GetFileContent($url);
		}
		
		if ( empty($buf) )	return false;

		if ( !$NotEncode )
		{
            $tmp = @gzinflate($buf);
			if ( !empty($tmp) && strlen($tmp) > 1)
                $buf = $tmp;
		}
		unset($tmp, $ch, $Header);

		return $buf;
	} 
	
/*======================================================================
    Function:	GetFileContent
    Purpose:	Get File Content like "file_get_contents" in PHP 5
	Var:		$file - file path
=======================================================================*/
	function GetFileContent($sFile, $iLength = null) 
	{
		$sContent = '';
		$fp = @fopen($sFile, 'r');
		if (!$fp)
			return '';

        if ($iLength !== null)
        	$sContent .= fread($fp, $iLength);
        else
			while (!feof($fp))
				$sContent .= fread($fp, 512);

		fclose($fp);

		return $sContent;
	}

/*======================================================================
    Function:	getHeaders
    Purpose:	Get Server headers
	Var:		$url - Server url
=======================================================================*/	
	function getHeaders($url) {
		$uh = parse_url($url);
		if (empty($uh['host'])) return false;
		
			$fhost = $uh['host'];
			$fname = '/';
			if (!empty($uh['path']))
				$fname = $uh['path'];
			
			if (!empty($uh['query'])) 
				$fname.='?'.$uh['query'];
			
		$x='';
		$fp = @fsockopen($fhost, 80, $errno, $errstr, 1);
		
		if (empty($fp)) return false;
		
		fputs($fp,"HEAD $fname HTTP/1.0\nHOST: $fhost\n\n");
		while(!feof($fp)) $x.=fgets($fp,128);
		fclose($fp);
		
		if (empty($x)) return false;
		
		$x_ = explode("\n",$x);
		
		print_r($x);
		
		$headers = array();
	
		preg_match('/[\S]+(.+)/is',$x_[0],$code);
		$headers['code'] = @trim($code[1]);
	
		preg_match('/Location\:[\s]*([\S]+)/is',$x,$location);
		if (!empty($location[1]))
			$headers['location'] = @trim($location[1]);
		
	 return $headers;
	}
	
   
}

?>