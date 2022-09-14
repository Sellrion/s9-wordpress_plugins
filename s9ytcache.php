<?php
/*
	Plugin name: S9 YouTube Data Local Cache
	Plugin URI: http://school9-nt.ru
	Description: Backend for the video gallery on a site
	Version: 1.2.1
	Author: Anton Koroteev
	Author URI: http://school9-nt.ru
*/

//EXECUTE ACTIVATION
function ytcache_activate(){
	//Schedule cache update
	wp_schedule_event(time(), 'daily', 'ytcache_update_hook');
}

//EXECUTE DEACTIVATION
function ytcache_deactivate(){
	//Unschedule cache update
	wp_clear_scheduled_hook('ytcache_update_hook');
}

//REGISTER HOOKS
add_action('ytcache_update_hook', 'ytcache_update');
register_activation_hook( __FILE__, 'ytcache_activate' );
register_deactivation_hook( __FILE__, 'ytcache_deactivate' );

//CACHE UPDATER
function ytcache_update($ondemand = false){
	$starttime = microtime(true);
	
	//Wordpress database object
	global $wpdb;
	
	//Get settings
	$DB_SETTINGS = $wpdb->get_results("SELECT varname, currentvalue FROM site_vgsettings", ARRAY_A);
	
	//Rebuld settings
	$SETTINGS = array();
	for($i = 0;$i < count($DB_SETTINGS);$i++) $SETTINGS[$DB_SETTINGS[$i]['varname']] = $DB_SETTINGS[$i]['currentvalue'];
	
	//Memory saving
	unset($DB_SETTINGS);
	
	//Get playlists
    $PLAYLISTS_API = curlYTAPIREQUEST($SETTINGS['BASEURL'] . '/playlists?part=contentDetails,snippet&channelId=' . $SETTINGS['CHANNEL_ID'] . '&maxResults=' . $SETTINGS['MAXRESULTS_LITEMS'] . '&key=' . $SETTINGS['APIKEY']);
	
	if(!$PLAYLISTS_API) return;
    
    //Collect playlists info
    $PLAYLISTS = array();
	$MIXEDSIZE = count($PLAYLISTS_API['items']);
	for($i = 0; $i < $MIXEDSIZE; $i++){
        $PLAYLISTS[] = array(
                                'id' => $PLAYLISTS_API['items'][$i]['id'], 
                                'title' => $PLAYLISTS_API['items'][$i]['snippet']['title'], 
                                'description' => $PLAYLISTS_API['items'][$i]['snippet']['description'], 
                                'created' => strtotime($PLAYLISTS_API['items'][$i]['snippet']['publishedAt']), 
                                'vids' => '', 
                                'vids_api' => array(), 
                                'vcount' => $PLAYLISTS_API['items'][$i]['contentDetails']['itemCount']
                            );
    }
    
    //Get playlists from other pages
    if($PLAYLISTS_API['pageInfo']['totalResults'] > $PLAYLISTS_API['pageInfo']['resultsPerPage']){
        while(isset($PLAYLISTS_API['nextPageToken'])){
            $PLAYLISTS_API = curlYTAPIREQUEST($SETTINGS['BASEURL'] . '/playlists?part=contentDetails,snippet&channelId=' . $SETTINGS['CHANNEL_ID'] . '&maxResults=' . $SETTINGS['MAXRESULTS_LITEMS'] . '&pageToken=' . $PLAYLISTS_API['nextPageToken'] . '&key=' . $SETTINGS['APIKEY']);
            
            if(!$PLAYLISTS_API) return;
            
            $MIXEDSIZE = count($PLAYLISTS_API['items']);
            for($i = 0; $i < $MIXEDSIZE; $i++){
                $PLAYLISTS[] = array(
                                        'id' => $PLAYLISTS_API['items'][$i]['id'], 
                                        'title' => $PLAYLISTS_API['items'][$i]['snippet']['title'], 
                                        'description' => $PLAYLISTS_API['items'][$i]['snippet']['description'], 
                                        'created' => strtotime($PLAYLISTS_API['items'][$i]['snippet']['publishedAt']), 
                                        'vids' => '', 
                                        'vids_api' => array(), 
                                        'vcount' => $PLAYLISTS_API['items'][$i]['contentDetails']['itemCount']
                                    );
            }
        }
    }
    
    unset($PLAYLISTS_API);
    
    //Now we need video ids
    $MIXEDSIZE = count($PLAYLISTS);
    for($i = 0; $i < $MIXEDSIZE; $i++){
        $PLAYLIST_API = curlYTAPIREQUEST($SETTINGS['BASEURL'] . '/playlistItems?part=contentDetails&playlistId=' . $PLAYLISTS[$i]['id'] . '&maxResults=' . $SETTINGS['MAXRESULTS_PITEMS'] . '&key=' . $SETTINGS['APIKEY']);
        
        if(!$PLAYLIST_API) return;
        
        $plsize = count($PLAYLIST_API['items']);
        $vindex = 0;
        for($j = 0; $j < $plsize; $j++){
            if($PLAYLISTS[$i]['vids'] == ''){
                $PLAYLISTS[$i]['vids'] .= "'" . $PLAYLIST_API['items'][$j]['contentDetails']['videoId'] . "'";
            } else {
                $PLAYLISTS[$i]['vids'] .= ",'" . $PLAYLIST_API['items'][$j]['contentDetails']['videoId'] . "'";
            }
            
            if($PLAYLISTS[$i]['vids_api'][$vindex] == ''){
                $PLAYLISTS[$i]['vids_api'][$vindex] .= $PLAYLIST_API['items'][$j]['contentDetails']['videoId'];
            } else {
                $PLAYLISTS[$i]['vids_api'][$vindex] .= ',' . $PLAYLIST_API['items'][$j]['contentDetails']['videoId'];
            }
        }
        
        //Don't forget about other pages
        $vindex++;
        if($PLAYLIST_API['pageInfo']['totalResults'] > $PLAYLIST_API['pageInfo']['resultsPerPage']){
            while(isset($PLAYLIST_API['nextPageToken'])){
                $PLAYLIST_API = curlYTAPIREQUEST($SETTINGS['BASEURL'] . '/playlistItems?part=contentDetails&playlistId=' . $PLAYLISTS[$i]['id'] . '&maxResults=' . $SETTINGS['MAXRESULTS_PITEMS'] . '&pageToken=' . $PLAYLIST_API['nextPageToken'] . '&key=' . $SETTINGS['APIKEY']);
                
                if(!$PLAYLIST_API) return;
                
                $plsize = count($PLAYLIST_API['items']);
                for($j = 0; $j < $plsize; $j++){
                    if($PLAYLISTS[$i]['vids'] == ''){
                        $PLAYLISTS[$i]['vids'] .= "'" . $PLAYLIST_API['items'][$j]['contentDetails']['videoId'] . "'";
                    } else {
                        $PLAYLISTS[$i]['vids'] .= ",'" . $PLAYLIST_API['items'][$j]['contentDetails']['videoId'] . "'";
                    }
                    
                    if($PLAYLISTS[$i]['vids_api'][$vindex] == ''){
                        $PLAYLISTS[$i]['vids_api'][$vindex] .= $PLAYLIST_API['items'][$j]['contentDetails']['videoId'];
                    } else {
                        $PLAYLISTS[$i]['vids_api'][$vindex] .= ',' . $PLAYLIST_API['items'][$j]['contentDetails']['videoId'];
                    }
                } 
                
                $vindex++;
            }
        }
    }
    
    unset($PLAYLIST_API);
	
	//Well, now we need to request video info
	$YTVIDEOS = array();
    for($i = 0; $i < $MIXEDSIZE; $i++){
        $pages = count($PLAYLISTS[$i]['vids_api']);
        for($k = 0; $k < $pages; $k++){
            $PLVIDS = curlYTAPIREQUEST($SETTINGS['BASEURL'] . '/videos?part=snippet,contentDetails,statistics,status&id=' . $PLAYLISTS[$i]['vids_api'][$k] . '&maxResults=' . $SETTINGS['MAXRESULTS_VITEMS'] . '&key=' . $SETTINGS['APIKEY']);

            if(!$PLVIDS) return;

            $plvidsc = count($PLVIDS['items']);
            for($j = 0; $j < $plvidsc; $j++){
                $YTVIDEOS[$PLVIDS['items'][$j]['id']] = array(
                                                                'datepublished' => strtotime($PLVIDS['items'][$j]['snippet']['publishedAt']),
                                                                'title' => $PLVIDS['items'][$j]['snippet']['title'],
                                                                'description' => $PLVIDS['items'][$j]['snippet']['description'],
                                                                'thumbnail' => $PLVIDS['items'][$j]['snippet']['thumbnails']['medium']['url'],
                                                                'thumbnail_small' => $PLVIDS['items'][$j]['snippet']['thumbnails']['default']['url'],
                                                                'duration' => $PLVIDS['items'][$j]['contentDetails']['duration'],
                                                                'uploadStatus' => $PLVIDS['items'][$j]['status']['uploadStatus'],
                                                                'privacyStatus' => $PLVIDS['items'][$j]['status']['privacyStatus'],
                                                                'embeddable' => (int)$PLVIDS['items'][$j]['status']['embeddable'],
                                                                'viewCount' => $PLVIDS['items'][$j]['statistics']['viewCount']
                                                            );
            }

            //Other pages
            if($PLVIDS['pageInfo']['totalResults'] > $PLVIDS['pageInfo']['resultsPerPage']){
                 while(isset($PLVIDS['nextPageToken'])){
                     $PLVIDS = curlYTAPIREQUEST($SETTINGS['BASEURL'] . '/videos?part=snippet,contentDetails,statistics,status&id=' . $PLAYLISTS[$i]['vids_api'][$k] . '&maxResults=' . $SETTINGS['MAXRESULTS_VITEMS'] . '&pageToken=' . $PLVIDS['nextPageToken'] . '&key=' . $SETTINGS['APIKEY']);

                     if(!$PLVIDS) return;

                     $plvidsc = count($PLVIDS['items']);
                     for($j = 0; $j < $plvidsc; $j++){
                        $YTVIDEOS[$PLVIDS['items'][$j]['id']] = array(
                                                                        'datepublished' => strtotime($PLVIDS['items'][$j]['snippet']['publishedAt']),
                                                                        'title' => $PLVIDS['items'][$j]['snippet']['title'],
                                                                        'description' => $PLVIDS['items'][$j]['snippet']['description'],
                                                                        'thumbnail' => $PLVIDS['items'][$j]['snippet']['thumbnails']['medium']['url'],
                                                                        'thumbnail_small' => $PLVIDS['items'][$j]['snippet']['thumbnails']['default']['url'],
                                                                        'duration' => $PLVIDS['items'][$j]['contentDetails']['duration'],
                                                                        'uploadStatus' => $PLVIDS['items'][$j]['status']['uploadStatus'],
                                                                        'privacyStatus' => $PLVIDS['items'][$j]['status']['privacyStatus'],
                                                                        'embeddable' => (int)$PLVIDS['items'][$j]['status']['embeddable'],
                                                                        'viewCount' => $PLVIDS['items'][$j]['statistics']['viewCount']
                                                                    );
                     }
                 }
            }
        }
    }
    
	//We're done with youtube for now
	unset($PLVIDS);
	
	//Get local cache
	$DB_LOCALCACHE = $wpdb->get_results("SELECT * FROM site_vgcache", ARRAY_A);
    $DB_LOCALCACHE_PL = $wpdb->get_results("SELECT * FROM site_vgplaylists", ARRAY_A);
	
	//Rebuild scheme
	$LOCALCACHE = array();
	$MIXEDSIZE = count($DB_LOCALCACHE);
	for($i = 0; $i < $MIXEDSIZE; $i++){
		$LOCALCACHE[$DB_LOCALCACHE[$i]['ytid']] = array(
														'id' => $DB_LOCALCACHE[$i]['id'],
														'datepublished' => $DB_LOCALCACHE[$i]['datepublished'],
														'title' => $DB_LOCALCACHE[$i]['title'],
														'description' => $DB_LOCALCACHE[$i]['description'],
														'duration' => $DB_LOCALCACHE[$i]['duration'],
														'uploadStatus' => $DB_LOCALCACHE[$i]['uploadStatus'],
														'privacyStatus' => $DB_LOCALCACHE[$i]['privacyStatus'],
														'embeddable' => $DB_LOCALCACHE[$i]['embeddable'],
														'viewCount' => $DB_LOCALCACHE[$i]['viewCount']
														);
	}
	
	//Memory saving
	unset($DB_LOCALCACHE);
    
	//Make compare and update cache
    //Playlists
    $MIXEDSIZE = count($PLAYLISTS);
    $MIXEDSIZE_2 = count($DB_LOCALCACHE_PL);
    $p = false;
    for($i = 0; $i < $MIXEDSIZE; $i++){
        $p = false;
        for($j = 0; $j < $MIXEDSIZE_2; $j++){
            if($PLAYLISTS[$i]['id'] == $DB_LOCALCACHE_PL[$j]['ytid']){
                if(($PLAYLISTS[$i]['title'] != $DB_LOCALCACHE_PL[$j]['title']) || 
                   ($PLAYLISTS[$i]['description'] != $DB_LOCALCACHE_PL[$j]['description']) || 
                   ($PLAYLISTS[$i]['created'] != $DB_LOCALCACHE_PL[$j]['datepublished']) || 
                   ($PLAYLISTS[$i]['vids'] != $DB_LOCALCACHE_PL[$j]['ytvids']) || 
                   ($PLAYLISTS[$i]['vcount'] != $DB_LOCALCACHE_PL[$j]['vidscount'])
                  ){
                    $wpdb->update(
								'site_vgplaylists',
								array(
										'title' => $PLAYLISTS[$i]['title'],
										'description' => $PLAYLISTS[$i]['description'],
										'datepublished' => $PLAYLISTS[$i]['created'],
										'ytvids' => $PLAYLISTS[$i]['vids'],
										'vidscount' => $PLAYLISTS[$i]['vcount']
										),
								array('ytid' => $DB_LOCALCACHE_PL[$j]['ytid']),
								array('%s', '%s', '%d', '%s', '%d'),
								array('%s')
								);
                }
                $p = true;
                break;
            }
        }
        
        if(!$p){
            $wpdb->insert(
							'site_vgplaylists', 
							array(
                                    'ytid' => $PLAYLISTS[$i]['id'],
									'datepublished' => $PLAYLISTS[$i]['created'],
									'title' => $PLAYLISTS[$i]['title'],
									'description' => $PLAYLISTS[$i]['description'], 
									'ytvids' => $PLAYLISTS[$i]['vids'],
									'vidscount' => $PLAYLISTS[$i]['vcount'], 
                                ),
							array('%s', '%d', '%s', '%s', '%s', '%d')
                        );
        }
    }
    
    for($i = 0; $i < $MIXEDSIZE_2; $i++){
        $p = false;
        for($j = 0; $j < $MIXEDSIZE; $j++){
            if($DB_LOCALCACHE_PL[$i]['ytid'] == $PLAYLISTS[$j]['id']){
                $p = true;
                break;
            }
        }
        
        if(!$p){
            $wpdb->query($wpdb->prepare("DELETE FROM site_vgplaylists 
										WHERE ytid = %s", $DB_LOCALCACHE_PL[$i]['ytid']));
        }
    }
    
    
	//Init cURL session for files
	$YTREQUEST = curl_init();
	curl_setopt($YTREQUEST, CURLOPT_TIMEOUT, 5);
	curl_setopt($YTREQUEST, CURLOPT_USERAGENT, 'S9 Local Cache');
	$vimage_path = $_SERVER['DOCUMENT_ROOT'] . $SETTINGS['CACHEDIR'] . '/';
	
	//Stat counters
	$items_added = 0;
	$items_updated = 0;
	$images_updated = 0;
	$items_scanned = 0;
	$items_removed = 0;
	foreach($YTVIDEOS as $YTVIDEO_ID => $YTVIDEO_DATA){
		//If there is no such video in cache, add it
		if(!isset($LOCALCACHE[$YTVIDEO_ID])){
			$wpdb->insert(
							'site_vgcache',
							array(
									'ytid' => $YTVIDEO_ID,
									'datepublished' => $YTVIDEO_DATA['datepublished'],
									'title' => $YTVIDEO_DATA['title'],
									'description' => $YTVIDEO_DATA['description'],
									'duration' => $YTVIDEO_DATA['duration'],
									'uploadStatus' => $YTVIDEO_DATA['uploadStatus'],
									'privacyStatus' => $YTVIDEO_DATA['privacyStatus'],
									'embeddable' => $YTVIDEO_DATA['embeddable'],
									'viewCount' => $YTVIDEO_DATA['viewCount']
									),
							array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
							);
							
			//Video default image medium
			$vimage = fopen($vimage_path . $YTVIDEO_ID . '_medium.jpg', 'w');
			curl_setopt($YTREQUEST, CURLOPT_URL, $YTVIDEO_DATA['thumbnail']);
			curl_setopt($YTREQUEST, CURLOPT_FILE, $vimage);
			
			$vimage_result = curl_exec($YTREQUEST);
			if(!$vimage_result) cachelog('Cache update warning. cURL interface returned FALSE when retrieving image data. URL: ' . $YTVIDEO_DATA['thumbnail'] . ' Video ID: ' . $YTVIDEO_ID);
			fclose($vimage);
			
			$vimage = fopen($vimage_path . $YTVIDEO_ID . '_small.jpg', 'w');
			curl_setopt($YTREQUEST, CURLOPT_URL, $YTVIDEO_DATA['thumbnail_small']);
			curl_setopt($YTREQUEST, CURLOPT_FILE, $vimage);
			
			$vimage_result = curl_exec($YTREQUEST);
			if(!$vimage_result) cachelog('Cache update warning. cURL interface returned FALSE when retrieving image data. URL: ' . $YTVIDEO_DATA['thumbnail_small'] . ' Video ID: ' . $YTVIDEO_ID);
			fclose($vimage);
			
			$items_added++;
		} else {
			//Check for changes
			$p = false;
			foreach($LOCALCACHE[$YTVIDEO_ID] as $videodata_key => $videodata_value){
				if($videodata_key != 'id' && $YTVIDEO_DATA[$videodata_key] != $videodata_value){
					$p = true;
					break;
				}
			}
			
			//If something changed...
			if($p){
				$wpdb->update(
								'site_vgcache',
								array(
										'title' => $YTVIDEO_DATA['title'],
										'description' => $YTVIDEO_DATA['description'],
										'duration' => $YTVIDEO_DATA['duration'],
										'privacyStatus' => $YTVIDEO_DATA['privacyStatus'],
										'embeddable' => $YTVIDEO_DATA['embeddable'],
										'viewCount' => $YTVIDEO_DATA['viewCount']
										),
								array('id' => $LOCALCACHE[$YTVIDEO_ID]['id']),
								array('%s', '%s', '%s', '%d', '%d'),
								array('%s')
								);
								
				$items_updated++;
			}
			
			//If cache update was initiated by user, update images
			if($ondemand){
				//Medium
				if(file_exists($vimage_path . $YTVIDEO_ID . '_medium.jpg')) unlink($vimage_path . $YTVIDEO_ID . '_medium.jpg');
				$vimage = fopen($vimage_path . $YTVIDEO_ID . '_medium.jpg', 'w');
				curl_setopt($YTREQUEST, CURLOPT_URL, $YTVIDEO_DATA['thumbnail']);
				curl_setopt($YTREQUEST, CURLOPT_FILE, $vimage);
				
				$vimage_result = curl_exec($YTREQUEST);
				if(!$vimage_result) cachelog('Cache update warning. cURL interface returned FALSE when retrieving image data. URL: ' . $YTVIDEO_DATA['thumbnail'] . ' Video ID: ' . $YTVIDEO_ID);
				fclose($vimage);
				
				//Small
				if(file_exists($vimage_path . $YTVIDEO_ID . '_small.jpg')) unlink($vimage_path . $YTVIDEO_ID . '_small.jpg');
				$vimage = fopen($vimage_path . $YTVIDEO_ID . '_small.jpg', 'w');
				curl_setopt($YTREQUEST, CURLOPT_URL, $YTVIDEO_DATA['thumbnail_small']);
				curl_setopt($YTREQUEST, CURLOPT_FILE, $vimage);
				
				$vimage_result = curl_exec($YTREQUEST);
				if(!$vimage_result) cachelog('Cache update warning. cURL interface returned FALSE when retrieving image data. URL: ' . $YTVIDEO_DATA['thumbnail_small'] . ' Video ID: ' . $YTVIDEO_ID);
				fclose($vimage);
			} else {
				//Medium
				if(file_exists($vimage_path . $YTVIDEO_ID . '_medium.jpg') && time() >= filemtime($vimage_path . $YTVIDEO_ID . '_medium.jpg') + (int)$SETTINGS['DATACACHE_IMAGESMAXAGE']){
					unlink($vimage_path . $YTVIDEO_ID . '_medium.jpg');
					$vimage = fopen($vimage_path . $YTVIDEO_ID . '_medium.jpg', 'w');
					curl_setopt($YTREQUEST, CURLOPT_URL, $YTVIDEO_DATA['thumbnail']);
					curl_setopt($YTREQUEST, CURLOPT_FILE, $vimage);
					
					$vimage_result = curl_exec($YTREQUEST);
					if(!$vimage_result) cachelog('Cache update warning. cURL interface returned FALSE when retrieving image data. URL: ' . $YTVIDEO_DATA['thumbnail'] . ' Video ID: ' . $YTVIDEO_ID);
					fclose($vimage);
					$images_updated++;
				}
				
				//Small
				if(file_exists($vimage_path . $YTVIDEO_ID . '_small.jpg') && time() >= filemtime($vimage_path . $YTVIDEO_ID . '_small.jpg') + (int)$SETTINGS['DATACACHE_IMAGESMAXAGE']){
					unlink($vimage_path . $YTVIDEO_ID . '_small.jpg');
					$vimage = fopen($vimage_path . $YTVIDEO_ID . '_small.jpg', 'w');
					curl_setopt($YTREQUEST, CURLOPT_URL, $YTVIDEO_DATA['thumbnail_small']);
					curl_setopt($YTREQUEST, CURLOPT_FILE, $vimage);
					
					$vimage_result = curl_exec($YTREQUEST);
					if(!$vimage_result) cachelog('Cache update warning. cURL interface returned FALSE when retrieving image data. URL: ' . $YTVIDEO_DATA['thumbnail_small'] . ' Video ID: ' . $YTVIDEO_ID);
					fclose($vimage);
					$images_updated++;
				}
			}
		}
		
		$items_scanned++;
	}
	
	//Close cURL session
	curl_close($YTREQUEST);
	
	//Finally we need to check videos that have been deleted from channel, to delete it from cache
	foreach($LOCALCACHE as $LVIDEO_ID => $LVIDEO_DATA){
		if(!isset($YTVIDEOS[$LVIDEO_ID])){
			$wpdb->query($wpdb->prepare("DELETE FROM site_vgcache 
										WHERE id = %d", $LVIDEO_DATA['id']));
			
			//Delete images
			unlink($vimage_path . $LVIDEO_ID . '_medium.jpg');
			unlink($vimage_path . $LVIDEO_ID . '_small.jpg');
			
			$items_removed++;
		}
	}
	
	//That's all! Do clean
	unset($YTVIDEOS, $LOCALCACHE);
	
	//If update was on demand, reschedule regular update to prevent it from execution twice
	if($ondemand){
		wp_clear_scheduled_hook('ytcache_update_hook');
		wp_schedule_event(time() + 86400, 'daily', 'ytcache_update_hook');
	}
	
	$endtime = microtime(true);
	$timediff = $endtime - $starttime;
	
	//Write log
	$logphrase = '';
	$logphrase = ($ondemand) ? 'Cache successfuly updated on user demand. ' : 'Cache successfuly updated. ';
	$logphrase .= $items_scanned . ' items scanned. ' . $items_added . ' items were added to cache. ' . $items_updated . ' items were updated. ' . $items_removed . ' items removed from cache.';
	$logphrase .= (!$ondemand) ? ' ' . $images_updated . ' images updated.' : '';
	$logphrase .= ' Update took ' . round($timediff, 3) . ' seconds.';
	cachelog($logphrase);
	
	//Return result
	return $logphrase;
}

function curlYTAPIREQUEST($requesturl){
    //Init cURL session
	$YTREQUEST = curl_init();
	curl_setopt($YTREQUEST, CURLOPT_TIMEOUT, 5); //Request timeout
	curl_setopt($YTREQUEST, CURLOPT_HTTPGET, true); //Force GET method
	curl_setopt($YTREQUEST, CURLOPT_RETURNTRANSFER, true); //Return context
	curl_setopt($YTREQUEST, CURLOPT_USERAGENT, 'S9 Local Cache');
    
    curl_setopt($YTREQUEST, CURLOPT_URL, $requesturl);
    
    //Execute
	$YTREQUEST_RESULT = curl_exec($YTREQUEST);
    
    //Close cURL session
	curl_close($YTREQUEST);
	
    //Decode result
	if($YTREQUEST_RESULT){
		$YTRESULT_DECODED = json_decode($YTREQUEST_RESULT, true);
		if(!$YTRESULT_DECODED){
			cachelog('Cache update failed. Recieved FALSE when trying to decode playlistItems data from JSON. Request URI: ' . $requesturl);
			return false;
		}
		
		//Check if YouTube API has returned an error
		if(isset($YTRESULT_DECODED['error'])){
			cachelog('Cache update failed. Recieved error from YouTube API. ' . buildErrorMessageFromRequest($YTRESULT_DECODED['error']) . '. Request URI: ' . $requesturl);
			return false;
		}
	} else {
		cachelog('Cache update failed. cURL interface returned FALSE when retrieving playlistItems data. Request URI: ' . $requesturl);
		return false;
	}
    
    return $YTRESULT_DECODED;
}

//CACHE UPDATER FUNCTIONS
function cachelog($logphrase = 'no logphrase'){
	
	global $wpdb;
	$wpdb->insert(
					'site_customlog',
					array(
							'recorddate' => time(),
							'subsystem' => 'ytcache',
							'record' => $logphrase
							),
					array('%d', '%s', '%s')
					);
}

function buildErrorMessageFromRequest($request){
	$mstring = 'ERRORS: ';
	for($i = 0;$i < count($request['errors']);$i++){
		$mstring .= ($i == 0) ? ($i + 1) . '. ' : '; ' . ($i + 1) . '. ';
		foreach($request['errors'][$i] as $mitem_name => $mitem_value){
			$mstring .= $mitem_name . ': ' . $mitem_value . ', ';
		}
	}
	
	$mstring .= '; CODE: ' . $request['code'] . ', MESSAGE: ' . $request['message'];
	return $mstring;
}

//SETUP ENVIRONMENT
if(is_admin()){
	//Add settings page
	add_action('admin_menu',
	function() {
		add_options_page('Видеогалерея', 'Видеогалерея', 'manage_options', 'ytcache_options', 'execute_s9ytcacheAdminEnvironment');
	});
}

//ADMIN ENVIRONMENT
function execute_s9ytcacheAdminEnvironment(){
	global $wpdb;
	
	//If requested cache update
	$updres = '';
	if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dupd'])) $updres = ytcache_update(true);
	
	//Building html
	$htmlAdminEnvironment = '<div class="wrap">';
	$htmlAdminEnvironment .= '<h2>Настройка видеогалереи</h2>';
	$htmlAdminEnvironment .= $updres;
	$htmlAdminEnvironment .= '<table class="wp-list-table widefat fixed posts" cellspacing="0">';
	$htmlAdminEnvironment .= '<thead><tr>';
	$htmlAdminEnvironment .= '<th scope="col" class="manage-column column-title"  style="">ID</th>';
	$htmlAdminEnvironment .= '<th scope="col" class="manage-column column-title"  style="">YouTube ID</th>';
	$htmlAdminEnvironment .= '<th scope="col" class="manage-column column-title"  style="">Название</th>';
	$htmlAdminEnvironment .= '<th scope="col" class="manage-column column-title"  style="">Статус загрузки</th>';
	$htmlAdminEnvironment .= '<th scope="col" class="manage-column column-title"  style="">Встраиваемое</th>';
	$htmlAdminEnvironment .= '</tr></thead>';
	$htmlAdminEnvironment .= '<tbody id="the-list">';
	
	//Get the list of videos
	$DB_LOCALCACHE = $wpdb->get_results("SELECT * FROM site_vgcache ORDER BY datepublished ASC", ARRAY_A);
	$SIZE = count($DB_LOCALCACHE);
	
	for($i = 0;$i < $SIZE;$i++){
		$htmlAdminEnvironment .= '<tr>';
		$htmlAdminEnvironment .= '<td class="id column-id">' . $DB_LOCALCACHE[$i]['id'] . '</td>';
		$htmlAdminEnvironment .= '<td class="id column-id">' . $DB_LOCALCACHE[$i]['ytid'] . '</td>';
		$htmlAdminEnvironment .= '<td class="title column-title"><a href="https://www.youtube.com/watch?v=' . $DB_LOCALCACHE[$i]['ytid'] . '" title="">' . $DB_LOCALCACHE[$i]['title'] . '</a></td>';
		
		$status = '';
		switch($DB_LOCALCACHE[$i]['uploadStatus']){
			case 'processed': $status = 'Загружено и обработано'; break;
			case 'processing': $status = 'Обрабатывается'; break;
			case 'failed':
			case 'rejected':
				$status = 'Ошибка'; break;
		}
		
		$htmlAdminEnvironment .= '<td class="description column-description">' . $status . '</td>';
		$htmlAdminEnvironment .= ($DB_LOCALCACHE[$i]['embeddable'] == 1) ? '<td class="description column-description">Да</td>' : '<td class="description column-description">Нет</td>';
		$htmlAdminEnvironment .= '</tr>';
	}
	
	unset($DB_LOCALCACHE);
	
	$htmlAdminEnvironment .= '</tbody></table>';
	$htmlAdminEnvironment .= '<p><form method="post" action=""><input type="hidden" name="dupd" value="true" /><input type="submit" value="Обновить кэш" /></form></p>';
	
	//Print html
	echo $htmlAdminEnvironment . '</div>';
}
?>