<?php
/**
 * analis_html_page  
 * 
 * @param mixed $content 
 * @access public
 * @return void
 */
function analis_html_page($url,$content='')
{
	if ( empty($content) )
		$content = file_get_contents($url);
		
	//get host name from URL
	$parse_url = parse_url($url);
	$host_name = preg_replace('!www\.!sim','',$parse_url['host']);
	$match = array();
	
	// analis h1
	preg_match_all('!<h1[^>]*>(?<value>(.*?))</h1>!sim',$content,$match['h1']);

	// analis h2
	preg_match_all('!<h2[^>]*>(?<value>(.*?))</h2>!sim',$content,$match['h2']);

	// analis h3
	preg_match_all('!<h3[^>]*>(?<value>(.*?))</h3>!sim',$content,$match['h3']);

	// analis h4
	preg_match_all('!<h4[^>]*>(?<value>(.*?))</h4>!sim',$content,$match['h4']);

	// analis b
	preg_match_all('!<b(\s|>)([^>]*>|)(?<value>(.*?))</b>!sim',$content,$match['b']);

	// analis p
	preg_match_all('!<p[^>]*>(?<value>(.*?))</p>!sim',$content,$match['p']);
	
	// links list
	preg_match_all('!<a[^>]*href\s*=\s*("|\')?(?<link>.*?)("|\'|\s)[^>]*>(?<content>.*?)</a>!sim',$content,$match['links_list']);

	// analis title
	preg_match('!<title[^>]*>(?<value>(.*?))</title>!sim',$content,$match['title']);

	// analis meta description
	preg_match('!<meta[\s]*name=(:?"|\')description(:?"|\')[\s]*content=(:?"|\')(?<value>[^"]*)(:?"|\')[^>]*>!sim',$content,$match['meta_description']);

	// analis meta keyword
	preg_match('!<meta[\s]*name=(:?"|\')keywords(:?"|\')[\s]*content=(:?"|\')(?<value>[^"]*)(:?"|\')[^>]*>!sim',$content,$match['meta_keywords']);

	// analis meta encoding
	preg_match('!<meta[\s]*http-equiv=(:?"|\')Content-Type(:?"|\')[^>]*charset=(?<value>[^"]*)(:?"|\')[^>]*>!sim',$content,$match['meta_encoding']);

	
	// create analis array with all data about analised web page
	$analis = array();
	$analis['meta_encoding'] = trim(@$match['meta_encoding']['value']);
	$analis['meta_keywords'] = trim(@$match['meta_keywords']['value']);
	$analis['meta_description'] = trim(@$match['meta_description']['value']);
	$analis['title'] = trim(@$match['title']['value']);

	// tag <h1>
	$analis['h1']['with_tags'] = $match['h1']['value'];
	if(!empty($match['h1']['value'][0]))
	{
		foreach($match['h1']['value'] as $key_h1 => $value_h1)
		{
			$analis['h1']['without_tags'][] = trim(strip_tags($value_h1));
		}
	}
	else
	{
		$analis['h1']['without_tags'] = array();
	}

	// tag <h2>
	$analis['h2']['with_tags'] = $match['h2']['value'];
	if(!empty($match['h2']['value'][0]))
	{
		foreach($match['h2']['value'] as $key_h2 => $value_h2)
		{
			$analis['h2']['without_tags'] = trim(strip_tags($value_h2));
		}
	}
	else
	{
		$analis['h2']['without_tags'] = array();
	}

	// tag <h3>
	$analis['h3']['with_tags'] = $match['h3']['value'];
	if(!empty($match['h3']['value'][0]))
	{
		foreach($match['h3']['value'] as $key_h3 => $value_h3)
		{
			$analis['h3']['without_tags'][] = trim(strip_tags($value_h3));
		}
	}
	else
	{
		$analis['h3']['without_tags'] = array();
	}

	// tag <h4>
	$analis['h4']['with_tags'] = $match['h4']['value'];
	if(!empty($match['h4']['value'][0]))
	{
		foreach($match['h4']['value'] as $key_h4 => $value_h4)
		{
			$analis['h4']['without_tags'][] = trim(strip_tags($value_h4));
		}
	}
	else
	{
		$analis['h4']['without_tags'] = array();
	}

	// tag <b>
	$analis['b']['with_tags'] = $match['b']['value'];
	if(!empty($match['b']['value'][0]))
	{
		foreach($match['b']['value'] as $key_b => $value_b)
		{
			$analis['b']['without_tags'][] = trim(strip_tags($value_b));
		}
	}
	else
	{
		$analis['b']['without_tags'] = array();
	}

	// tag <p>
	$analis['p']['with_tags'] = $match['p']['value'];
	if(!empty($match['p']['value'][0]))
	{
		foreach($match['p']['value'] as $key_p => $value_p)
		{
			$analis['p']['without_tags'][] = trim(strip_tags($value_p));
		}
	}
	else
	{
		$analis['p']['without_tags'] = array();
	}

	// list links
	for($key = 0; $key < count($match['links_list'][0]); $key++)
	{
		$match['links_list']['link'][$key] = trim($match['links_list']['link'][$key]);
		if ( strpos($match['links_list']['link'][$key], 'javascript') === 0 
			|| strpos($match['links_list']['link'][$key], 'javascript') === '#'
			|| empty($match['links_list']['link'][$key]))
		{
			continue;
		}
		
		if(preg_match('!^http!sim',$match['links_list']['link'][$key]) AND
			!preg_match('!http[s]*://[^/]*'.$host_name.'!sim',$match['links_list']['link'][$key]))
		{
			$o = true;
			$analis['list_links']['outside_links']['link'][] = trim($match['links_list']['link'][$key]);
			$analis['list_links']['outside_links']['content_with_tags'][] = trim($match['links_list']['content'][$key]);
			$analis['list_links']['outside_links']['content_without_tags'][] = trim(strip_tags($match['links_list']['content'][$key]));
		}
		else
		{
			$o = false;
			$analis['list_links']['inside_links']['link'][] = trim($match['links_list']['link'][$key]);
			$analis['list_links']['inside_links']['content_with_tags'][] = trim($match['links_list']['content'][$key]);
			$analis['list_links']['inside_links']['content_without_tags'][] = trim(strip_tags($match['links_list']['content'][$key]));
		}
		
		if(preg_match('!re[lv][\s]*=[\s]*("|\'|\s)?nofollow("|\'|\s)?!sim',$match['links_list'][0][$key]) && $o)
		{
			$analis['list_links']['nofollow']['link'][] = trim($match['links_list']['link'][$key]);
			$analis['list_links']['nofollow']['content_with_tags'][] = trim($match['links_list']['content'][$key]);
			$analis['list_links']['nofollow']['content_without_tags'][] = trim(strip_tags($match['links_list']['content'][$key]));
		}
	}
	
	$analis['list_links']['count_all_links'] = @count($match['links_list'][0]);
	$analis['list_links']['count_outside_links'] = @count($analis['list_links']['outside_links']['link']);
	$analis['list_links']['count_inside_links'] = @count($analis['list_links']['inside_links']['link']);
	$analis['list_links']['count_nofollow'] = @count($analis['list_links']['nofollow']['link']);

	return $analis;
}
