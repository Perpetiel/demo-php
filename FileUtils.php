<?php
error_reporting(E_ERROR);
class FileUtils {
	
	/**
	* Total resource download size from a specific URL.
	*/
	public static function getTotalDownloadSize($url) {
		if (!isset($url)) {
			throw new Exception('URL not specified.');
		}
		
		$contentInfo = FileUtils::getContentInfo($url); // get_headers($url, 1);
		
		$size = $contentInfo['length'];
		if ($contentInfo['type'] === 'text/html') {
			$html = FileUtils::getContent($url); // file_get_contents($url);
			$dom = new DomDocument();
			$dom->loadHTML($html);
			
			$size = strlen($html);
			$size += FileUtils::getHTMLDownloadSize($url, $dom);
		}
		
		print "Total download size: " . $size . " bytes";
	}
	
	/**
	* Total download size of all embedded resources.
	*/
	public static function getHTMLDownloadSize($url, $dom) {
		$size = 0;
		$size += FileUtils::getEmbeddedDownloadSize($url, $dom, 'img', 'src');
		$size += FileUtils::getEmbeddedDownloadSize($url, $dom, 'script', 'src');
		$size += FileUtils::getEmbeddedDownloadSize($url, $dom, 'link', 'href');
		$size += FileUtils::getEmbeddedDownloadSize($url, $dom, 'video', 'src');
		$size += FileUtils::getEmbeddedDownloadSize($url, $dom, 'audio', 'src');
		$size += FileUtils::getEmbeddedDownloadSize($url, $dom, 'iframe', 'src');
		$size += FileUtils::getEmbeddedDownloadSize($url, $dom, 'embed', 'src');
		$size += FileUtils::getEmbeddedDownloadSize($url, $dom, 'object', 'data');
		$size += FileUtils::getEmbeddedDownloadSize($url, $dom, 'source', 'src');
		return $size;
	}
	
	/**
	* Specific embedded resources download size.
	*/
	public static function getEmbeddedDownloadSize($baseURL, $dom, $tag, $attr) {
		$size = 0;
		$elements = $dom->getElementsByTagName($tag);
		foreach ($elements as $element) {
			$url = $element->getAttribute($attr);
			if(strpos($url, '://') === false) {
				$url = $baseURL . '/' . $url; 
			}
			$size += FileUtils::getContentInfo($url)['length'];
		}
		print $tag . "\trequets: " . $elements->length . "\tsize: " . $size . " bytes" . PHP_EOL;
		return $size;
	}
	
	/**
	* URL content info: length and type.
	*/
	public static function getContentInfo($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		
		$contentLength = 0;
		if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {
			$contentLength = $matches[1];
		}
		
		$contentType = '';
		if (preg_match('/Content-Type: (\w+\/\w+)/', $data, $matches)) {
			$contentType = $matches[1];
		}
		
		return array('length' => $contentLength, 'type' => $contentType);
	}
	
	/**
	* URL content.
	*/
	public static function getContent($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}
FileUtils::getTotalDownloadSize($argv[1]);
?>