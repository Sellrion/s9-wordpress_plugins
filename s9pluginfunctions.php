<?php
//Usefull functions for plugins
function linkRemove_Request($link, $request){
	$requests = explode(',', $request);
	if(count($requests) == 0) return;
	
	$request1 = explode('?', $link);
	$request2 = explode('&', $request1[1]);
	$request_clean = '';
	$rc = count($request2);
	for($i = 0;$i < $rc;$i++){
		$p = false;
		for($j = 0;$j < count($requests);$j++) if(strstr($request2[$i], $requests[$j])) $p = true;
		if(!$p){
			if($request_clean == ''){
				$request_clean = ($rc > 1) ? '?' . $request2[$i] : $request2[$i];
			} else {
				$request_clean .= '&' . $request2[$i];
			}
		}
	}
	
	return $request1[0] . $request_clean;
}
?>