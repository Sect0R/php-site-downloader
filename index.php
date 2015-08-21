<?php
//TODO: style images
//TODO:

class siteDownloader
{
    var $WP;
    var $ResultPath;
    var $target_url = '';
    var $page = '';
    var $cssUrl = '';

    var $filesStyleList = array();
    var $filesScriptList = array();
    var $filesImagesList = array();

    function __construct($target_url)
    {
        require('webpages.php');
        $this->WP = new WebPages(30, dirname(__FILE__).'/cookie');
        $this->ResultPath = dirname(__FILE__).'/result';
        $this->target_url = $target_url;
    }

    function forceDir($path, $mode = 0777) {

        $dirs = explode('/' , $path);
        $count = count($dirs);
        $path = $this->ResultPath;
        for ($i = 0; $i < $count; ++$i) {
            $path .= '/' . $dirs[$i];
            if (!is_dir($path) && !@mkdir($path, $mode)) {
                return false;
            }
        }
        return true;
    }

    function delFirstSlash($string)
    {
        if ( substr($string, 0, 1) == '/' )
            return substr($string, 1, strlen($string));
        else
            return $string;
    }

    function downloadLinkTags()
    {
        // check <link>
        preg_match_all('/<link[^>]+>/is', $this->page, $link);
        if ( !empty($link[0]) )
            foreach ($link[0] as $link_val)
            {
                preg_match('/rel=["|\']([^["|\']+)/is', $link_val, $rel);
                preg_match('/href=["|\']([^["|\']+)/is', $link_val, $href);

                if ( $rel[1] == 'stylesheet' || $rel[1] == 'apple-touch-icon-precomposed')
                {
                    $newSrc = $this->downloadFile($href[1]);
                    $this->page = str_ireplace($href[1], $newSrc, $this->page);

                    if ( $rel[1] == 'stylesheet' )
                        $this->filesStyleList[] = $newSrc;
                    else
                        $this->filesImagesList[] = $newSrc;
                }
            }
    }

    function downloadScriptTags()
    {
        // check <script>
        preg_match_all('/<script[^>]+>/is', $this->page, $link);

        if ( !empty($link[0]) )
            foreach ($link[0] as $link_val)
            {
                preg_match('/src=["|\']([^["|\']+)/is', $link_val, $href);

                if ( !empty($href[1] ) )
                {
                    $newSrc = $this->downloadFile($href[1]);
                    $this->page = str_ireplace($href[1], $newSrc, $this->page);

                    $this->filesScriptList[] = $newSrc;
                }
            }
    }

    function downloadImgTags()
    {
        // check <script>
        preg_match_all('/<img[^>]+>/is', $this->page, $link);
        if ( !empty($link[0]) )
            foreach ($link[0] as $link_val)
            {
                preg_match('/src=["|\']([^["|\']+)/is', $link_val, $href);

                if ( !empty($href[1] ) && substr($href[1], 0, 10) != 'data:image' )
                {
                    $newSrc = $this->downloadFile($href[1], true);
                    $this->page = str_ireplace($href[1], $newSrc, $this->page);

                    $this->filesImagesList[] = $newSrc;
                }
                else
                {
                    preg_match('/data-src=["|\']([^["|\']+)/is', $link_val, $href);

                    if ( !empty($href[1]) )
                    {
                        $newSrc = $this->downloadFile($href[1], true);
                        $this->page = str_ireplace($href[1], $newSrc, $this->page);

                        $this->filesImagesList[] = $newSrc;
                    }
                }
            }
    }

    function downloadFilesFromCSS()
    {
        foreach ($this->filesStyleList as $file)
        {
            $dirLevels = substr_count($file, '/');
			$this->downloadDataFromCss($this->ResultPath.'/'.$file, $dirLevels);
        }
    }

	function downloadDataFromCss($filepath, $dirLevels = false)
	{
		$fileContent = file_get_contents( $filepath );

		$replacedUrls = array();

		preg_match_all('/url\(["\']*([^\)|"|\']+)/is', $fileContent, $urls);
		if ( !empty($urls[1]) )
		{
			foreach ($urls[1] as $url)
			{
				if ( !strpos($url, ';base64,') ) {
					$newSrc = $this->downloadFile($url, true);
					if ( empty($replacedUrls[$url]) ) {
						$fileContent = str_ireplace($url, ($dirLevels ? str_repeat('../', $dirLevels) : '') . $newSrc, $fileContent);
						$replacedUrls[$url] = ($dirLevels ? str_repeat('../', $dirLevels) : '') . $newSrc;
					}
				}
			}
			file_put_contents( $filepath, $fileContent);
		}
	}

    function downloadFile($url, $isBinFile = false)
    {
        $tHost = parse_url($this->target_url);
        $tHost = $tHost['scheme'].'://'.$tHost['host'];

        $r = parse_url($url);
        $filePath = $r['path'];

        if ( strpos($filePath, '/') !== false )
        {
            if ( $filePath[0] == '/' )
                $filePath = substr($filePath, 1, strlen($filePath));

            $this->forceDir(dirname($filePath));
        }
        
        
		if ( !empty($this->cssUrl) )
			$tHost = $this->cssUrl;

        if ( empty($r['host']) )
        {
            $url = $tHost.'/'.$filePath;
        }

        if ( !file_exists( $this->ResultPath.'/'.$filePath ) )
        {
            if ( !$isBinFile )
                $fileSrc = $this->WP->GetPage($url);
            else
                $fileSrc = $this->WP->GetPage($url, false, true);

            if ( !empty($fileSrc) )
                file_put_contents($this->ResultPath.'/'.$filePath, $fileSrc);
        }

        return $filePath;
    }

    function download( $outputFilename = 'page.html')
    {
       $this->page = $this->WP->GetPage($this->target_url);
        if ( !$this->page )
            die('error');

        $this->page = preg_replace('/<base[^>]+>/is', '', $this->page);

        $this->downloadLinkTags();
        $this->downloadScriptTags();
        $this->downloadImgTags();

        $this->downloadFilesFromCSS();

        file_put_contents($this->ResultPath.'/'.$outputFilename, $this->page);

    }
}

set_time_limit(0);

$sd = new siteDownloader('http://sanit.by/');
$sd->cssUrl = 'http://sanit.by/catalog/view/theme/sanit.by/stylesheet';
$sd->downloadDataFromCss('styles.css');
?>