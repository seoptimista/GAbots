<?php
/**
TRACK SEARCH ENGINE BOTS IN GOOGLE ANALYTICS

GA for Search Bots Copyright 2011 Cardinal Path
Licensed under Creative Commons - Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
http://creativecommons.org/licenses/by-sa/3.0/

Original code by Adrian Vender https://plus.google.com/118195014774946875457
Code refinements by Eduardo Cereto https://plus.google.com/103773004936256152223
**/

  // Create a new ‘bots only’ profile in your Google Analytics account, Copy to the $GA_SB_ACCOUNT variable the numbers from the UA-XXXXXX-YY Property ID for that profile (not the 'UA', respect the 'MO')
  $GA_SB_ACCOUNT = "MO-XXXXXX-YY";

  // Tracker version.
  define("VERSION", "4.4sh");

  // The last octect of the IP address is removed to anonymize the user.
  function getIP($remoteAddress) {
    if (empty($remoteAddress)) {
      return "";
    }

    // Capture the first three octects of the IP address and replace the forth
    // with 0, e.g. 124.455.3.123 becomes 124.455.3.0
    $regex = "/^([^.]+\.[^.]+\.[^.]+\.).*/";
    if (preg_match($regex, $remoteAddress, $matches)) {
      return $matches[1] . "0";
    } else {
      return "";
    }
  }

  // Generate a visitor id for this hit.
  // Use a hash value of the user agent string
  function getVisitorId($userAgent) {
    $message = "";
    $message = $userAgent;
    $md5String = md5($message);
    return "0x" . substr($md5String, 0, 16);
  }

  // Get a random number string.
  function getRandomNumber() {
    return rand(0, 0x7fffffff);
  }

  // Sends a gif request to GA server
  function writeGifData($utmUrl) {
	$cu = curl_init();
	curl_setopt($cu, CURLOPT_HEADER, 1);
	curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($cu, CURLOPT_URL, $utmUrl);
	curl_setopt($cu,CURLOPT_HTTPHEADER,array('Content-Type: image/gif','Cache-Control: private, no-cache, no-cache=Set-Cookie, proxy-revalidate','HeaderName2: HeaderValue2','Pragma: no-cache','Expires: Wed, 17 Sep 1975 21:32:10 GMT'));
	$code = curl_exec($cu);
	curl_close($cu);
  }

  // Track a page view. Makes a server side request to Google Analytics.
  function trackPageView() {

	//Declare $GA_SB_ACCOUNT global
	global $GA_SB_ACCOUNT;

	// load bot config
	require_once('botconfig.php');

	// Get the user agent and attempt to match it with an item in the $bots array
    $userAgent=$_SERVER['HTTP_USER_AGENT'];
    $botname="";
	foreach( $bots as $pattern => $bot ) {
		if ( preg_match( '#'.$pattern.'#i' , $userAgent ) == 1 ) {
			$botname = preg_replace ( "/\\s{1,}/i" , '-' , $bot );		//Bot Name;
			break;
		}
	}

	//Exit GA for Search Bots script if no identified botname exists
    if($botname=="") {
		return false;
	}

  	$timeStamp = time();

	// Get the hostname for the utmhn parameter
    $domainName = $_SERVER["SERVER_NAME"];
    if (empty($domainName)) {
      $domainName = "";
    }

    // Get the referrer from the utmr parameter.
    $documentReferer = $_SERVER["HTTP_REFERER"];
    if (empty($documentReferer) && $documentReferer !== "0") {
      $documentReferer = "-";
    } else {
      $documentReferer = $documentReferer;
    }

	// Get the URI of the page
    $documentPath = $_SERVER["REQUEST_URI"];
    if (empty($documentPath)) {
      $documentPath = "";
    } else {
      $documentPath = $documentPath;
    }

	// Get Google Analytics profile ID
    $account = $GA_SB_ACCOUNT;

    $visitorId = getVisitorId($userAgent);

    $utmGifLocation = "http://www.google-analytics.com/__utm.gif";

    // Construct the gif hit url.
    $utmUrl = $utmGifLocation . "?" .
        "utmwv=" . VERSION .
        "&utmn=" . getRandomNumber() .
        "&utmhn=" . urlencode($domainName) .
        "&utmr=" . urlencode($documentReferer) .
        "&utmp=" . urlencode($documentPath) .
    	"&utme=8(date-time)9(".urlencode(date("Y-m-d_H:i:s",time())).")11(3)" . //Creates a page-scope custom variable of 'date-time' with a timestamp
        "&utmac=" . $account .
        "&utmcc=__utma%3D999.999.999.999.999.1%3B%2B__utmz%3D999.999.1.1.utmccn%3Dsearch-engine-bots%7Cutmcsr%3D".$botname."%7Cutmcmd%3Dbots%3B" .
        "&utmvid=" . $visitorId .
        "&utmip=" . getIP($_SERVER["REMOTE_ADDR"]);

    // Finally write the gif data to the response.
    writeGifData($utmUrl);
  }

  trackPageView();
?>
