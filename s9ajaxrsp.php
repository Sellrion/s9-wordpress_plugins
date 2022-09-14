<?php
/*
	Plugin name: S9 AJAX responder
	Plugin URI: http://school9-nt.ru
	Description: Responses on AJAX requests from frontend
	Version: 1.1.1
	Author: Anton Koroteev
	Author URI: http://school9-nt.ru
*/

if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'S9AJAX-ENGINE'){
	//Main response object
	$response = array(
						'system_status' => 'UNKNOWN',
						'exeption' => ''
						);
	
	//Main script
	if(isset($_POST['do'])){
		//Update the capcha image
		switch($_POST['do']){
			case 'c_imageupdate':
				if(isset($_POST['ms_session'])){
					$s = $_POST['ms_session'];
					
					//Try to take real ip
					$uip = '';
					if (isset($_SERVER['HTTP_CLIENT_IP'])) $uip = $_SERVER['HTTP_CLIENT_IP']; else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $uip = $_SERVER['HTTP_X_FORWARDED_FOR']; else $uip = $_SERVER['REMOTE_ADDR'];
					
					$idhash = md5($_SERVER['HTTP_USER_AGENT'] . $uip);
					
					$erecs = $wpdb->get_results($wpdb->prepare("SELECT * FROM site_fback_tchallenges 
																WHERE sessionhash = %s 
																AND idhash = %s 
																AND tc_used = 0 
																ORDER BY tc_date DESC", $s, $idhash), 
												ARRAY_A);
					
					if(isset($erecs[0])){
						//Delete old captcha file
						if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/files/s9msgrcache/capchas/' . $erecs[0]['tc_image'])) unlink($_SERVER['DOCUMENT_ROOT'] . '/files/s9msgrcache/capchas/' . $erecs[0]['tc_image']);
						
						//New capcha image
                        require_once("s9msgr/c-builder/c_builder.php");
						$newc = new SimpleCaptcha();
						
						//Update database
						$retval = $wpdb->update(
												'site_fback_tchallenges',
												array(
														'tc_image' => $newc->c_imagename,
														'tc_solve' => $newc->c_text
														),
												array(
														'sessionhash' => $s,
														'idhash' => $idhash
														),
												array('%s', '%s'),
												array('%s', '%s')
												);
						/*$retval = $wpdb->query("UPDATE site_fback_tchallenges 
						SET tc_image = '" . $newc->c_imagename . "', tc_solve = '" . $newc->c_text . "'  
						WHERE sessionhash = '" . $s . "' 
						AND idhash = '" . $idhash . "'");*/
						
						//Build response
						if($retval){
							$response['system_status'] = 'OK';
							$response['c_imagename'] = $newc->c_imagename;
						} else {
							$response['system_status'] = 'ERROR';
							$response['exeption'] = 'Database error';
						}
					} else {
						//If session not found, start new one
						$s = md5(uniqid(microtime(), true));
						
						//New capcha image
                        require_once("s9msgr/c-builder/c_builder.php");
						$newc = new SimpleCaptcha();
						
						//Write session
						$wpdb->insert(
										'site_fback_tchallenges', 
										array(
												'sessionhash' => $s,
												'idhash' => $idhash,
												'tc_solve' => $newc->c_text,
												'tc_image' => $newc->c_imagename,
												'tc_date' => current_time('timestamp'),
												'tc_used' => 0
										),
										array('%s', '%s', '%s', '%s', '%d', '%d')
									);
						
						$response['system_status'] = 'OK';
						$response['session'] = $s;
						$response['c_imagename'] = $newc->c_imagename;
					}
				}
				
				
				//Echo response
				header("Content-Type: text/javascript");
				echo json_encode($response);
				break;
			case 'get_dashboardconfig':
				//Get scheme
				$retval = $wpdb->get_results("SELECT varname, currentvalue FROM site_dashboardsettings", ARRAY_A);
				$retval2 = $wpdb->get_results("SELECT scatid, slideindex, slidehtml FROM site_dashboardslides 
												WHERE isactive = 1 
												ORDER BY slideindex ASC", ARRAY_A);
				
				if($retval && $retval2){
					$response['system_status'] = 'OK';
					
					//Rebuild config result
					$response['config'] = array();
					for($i = 0; $i < count($retval); $i++) $response['config'][$retval[$i]['varname']] = $retval[$i]['currentvalue'];
					
					//Rebuil slidescheme to an usefull array
					$response['slidescheme'] = array();
					for($i = 0; $i < count($retval2); $i++) $response['slidescheme'][$retval2[$i]['scatid']][] = $retval2[$i]['slidehtml'];
				} else {
					$response['system_status'] = 'ERROR';
					$response['exeption'] = 'Database error';
				}
			
				//Echo response
				header("Content-Type: text/javascript");
				echo json_encode($response);
				break;
            case 'get_videogalleryplaylists':
                $retval = $wpdb->get_results("SELECT id, title, vidscount 
                                              FROM site_vgplaylists 
                                              ORDER BY rand()", ARRAY_A);
                if($retval){
                    $response['system_status'] = 'OK';
                    $response['playlists'] = $retval;
                } else {
                    $response['system_status'] = 'ERROR';
					$response['exeption'] = 'Database error';
                }
                
                //Echo response
                header("Content-Type: text/javascript");
				echo json_encode($response);
                break;
            case 'get_videogalleryplaylist':
                if(isset($_POST['plid'])){
                    $plid = intval($_POST['plid']);
                    if($plid == 666){
                        $retval = $wpdb->get_results("SELECT id, ytid, datepublished, title, description, duration, uploadStatus, privacyStatus, embeddable, viewCount 
                                                      FROM site_vgcache 
                                                      ORDER BY viewCount DESC 
                                                      LIMIT 12", 
                                                    ARRAY_A);
                        
                        //Make some changes
                        $retval_count = count($retval);
                        for($i = 0; $i < $retval_count; $i++){
                            $retval[$i]['datepublished'] = date('d.m.Y', $retval[$i]['datepublished']);
                            $duration = new DateInterval($retval[$i]['duration']);
                            $retval[$i]['duration'] = ($duration->h > 0) ? $duration->format('%h:%I:%S') : $duration->format('%I:%S');
                        }
                    } else {
                        $retval = $wpdb->get_results($wpdb->prepare("SELECT ytvids FROM site_vgplaylists 
                                                                    WHERE id = %d", $plid), ARRAY_A);
                        $ytvids = $retval[0]['ytvids'];
                        
                        $retval = $wpdb->get_results("SELECT id, ytid, datepublished, title, description, duration, viewCount 
                                                      FROM site_vgcache  
                                                      WHERE ytid IN(" . $ytvids . ") 
                                                      ORDER BY datepublished DESC", 
                                                    ARRAY_A);
                        
                        //Make some changes
                        $retval_count = count($retval);
                        for($i = 0; $i < $retval_count; $i++){
                            $retval[$i]['datepublished'] = date('d.m.Y', $retval[$i]['datepublished']);
                            $duration = new DateInterval($retval[$i]['duration']);
                            $retval[$i]['duration'] = ($duration->h > 0) ? $duration->format('%h:%I:%S') : $duration->format('%I:%S');
                        }
                    }
                    if($retval){
                        $response['system_status'] = 'OK';
                        $response['playlist'] = $retval;
                    } else {
                        $response['system_status'] = 'ERROR';
                        $response['exeption'] = 'Database error';
                    }
                } else {
                    $response['system_status'] = 'ERROR';
                    $response['exeption'] = 'No valid playlist id provided.';
                }
                
                //Echo response
                header("Content-Type: text/javascript");
				echo json_encode($response);
                break;
            case 'get_photoalbum':
                $excludedgalleriesids = "";
                $nggoptions = $wpdb->get_results($wpdb->prepare("SELECT * FROM site_ngg_options"), ARRAY_A);
                for($i = 0; $i < count($nggoptions); $i++){
                    if($nggoptions[$i]['name'] == 'siteGallery_exluded_galleryids'){
                        $excludedgalleriesids = $nggoptions[$i]['value'];
                        break;
                    }
                }
                
                $photoalbum_raw = $wpdb->get_results($wpdb->prepare("SELECT gallery.gid, gallery.name, gallery.path, gallery.title, gallery.galdesc, gallery.previewpic, coverpic.pid, 
                                                                     coverpic.filename, coverpic.imagedate 
                                                                     FROM site_ngg_gallery AS gallery 
                                                                     LEFT JOIN site_ngg_pictures AS coverpic ON(gallery.previewpic = coverpic.pid) 
                                                                     WHERE gallery.gid NOT IN (" . $excludedgalleriesids . ") 
                                                                     ORDER BY coverpic.imagedate DESC"), 
                                                     ARRAY_A);
                $gcount = count($photoalbum_raw);
                $current_gyear = '';
                $gyear_index = -1;
                $PHOTOALBUM = array();
                for($i = 0; $i < $gcount; $i++){
                    $created_raw = strtotime($photoalbum_raw[$i]['imagedate']);
                    $gyear = date('Y', $created_raw);
                    if($gyear != $current_gyear){
                        $gyear_index++;
                        $PHOTOALBUM[] = array(
                                                'year' => $gyear, 
                                                'loaded' => false, 
                                                'galleries' => array()
                                             );
                        $current_gyear = $gyear;
                    }
                    
                    $localpath = (!str_ends_with($photoalbum_raw[$i]['path'], '/')) ? $photoalbum_raw[$i]['path'] . '/' : $photoalbum_raw[$i]['path'];
                    $localpath = (!str_starts_with($localpath, '/')) ? '/' . $localpath : $localpath;
                    $PHOTOALBUM[$gyear_index]['galleries'][] = array(
                                                                        'id' => $photoalbum_raw[$i]['gid'], 
                                                                        'name' => $photoalbum_raw[$i]['name'], 
                                                                        'title' => $photoalbum_raw[$i]['title'], 
                                                                        'description' => htmlspecialchars($photoalbum_raw[$i]['galdesc']), 
                                                                        'datecreated' => date('d.m.Y', $created_raw), 
                                                                        'cover' => $localpath . 'thumbs/thumbs_' . rawurlencode($photoalbum_raw[$i]['filename']) 
                                                                    );
                }
                
                $response['system_status'] = 'OK';
                $response['photoalbum'] = $PHOTOALBUM;
                
                //Echo response
                header("Content-Type: text/javascript");
				echo json_encode($response);
                break;
            case 'get_pagegallery':
                if(isset($_POST['pid'])){
                    $id = intval($_POST['pid']);
                    $gallery = $wpdb->get_results($wpdb->prepare("SELECT gallery.gid, gallery.name, gallery.path, gallery.title, gallery.galdesc, picture.pid, picture.filename, picture.description,                                               picture.alttext, picture.imagedate, picture.meta_data 
                                                                  FROM site_ngg_gallery AS gallery 
                                                                  LEFT JOIN site_ngg_pictures AS picture ON(gallery.gid = picture.galleryid) 
                                                                  WHERE gallery.gid = " . $id . " 
                                                                  ORDER BY picture.pid ASC", '%d'), 
                                                  ARRAY_A);
                    
                    if(count($gallery) != 0){
                        $gallerypath = explode('/', $gallery[0]['path']);
                        $galleryfolder = ($gallery[0]['path'][strlen($gallery[0]['path']) - 1] != '/') ? $gallerypath[count($gallerypath) - 1] : $gallerypath[count($gallerypath) - 2];
                        $localpath = (!str_ends_with($gallery[0]['path'], '/')) ? $gallery[0]['path'] . '/' : $gallery[0]['path'];
                        $localpath = (!str_starts_with($localpath, '/')) ? '/' . $localpath : $localpath;
                        $galleryinfo = array(
                                             'id' => $gallery[0]['gid'], 
                                             'name' => ($gallery[0]['name']) ? $gallery[0]['name'] : '', 
                                             'remotepath' => 'https://data.school9-nt.ru/publicgallery/' . $galleryfolder . '/', 
                                             'localpath' => $localpath, 
                                             'title' => ($gallery[0]['title']) ? $gallery[0]['title'] : '', 
                                             'description' => ($gallery[0]['galdesc']) ? $gallery[0]['galdesc'] : ''
                                            );
                        
                        $imgs = count($gallery);
                        $rem = $imgs;
                        $icount = 0;
                        $tpl = 0;
                        $pagegallery = array(
                                             'title' => trim($galleryinfo['title']), 
                                             'name' => $galleryinfo['name'], 
                                             'desc' => trim($galleryinfo['description']), 
                                             'galleryid' => $galleryinfo['id'],  
                                             'imgcount' => $imgs, 
                                             'pieces' => array(), 
                                             'images' => array()
                                            );
                        while($rem > 0){
                            switch($rem){
                                case 1: $tpl = mt_rand(1, 2); break;
                                case 2: $tpl = mt_rand(1, 6); break;
                                default: $tpl = mt_rand(1, 9); break;
                            }
                            $pagegallery_piece = array(
                                                       'pattern' => $tpl, 
                                                       'placed' => false,
                                                       'images' => array()
                                                      );
                             
                            $meta = unserialize($gallery[$icount]['meta_data']);
                            $pagegallery_piece['images'][] = $gallery[$icount]['pid'];
                            $created = ($meta && $meta['created_timestamp']) ? intval($meta['created_timestamp']) : '';
                            $pagegallery['images'][] = array(
                                                             'id' => $gallery[$icount]['pid'], 
                                                             'title' => trim($gallery[$icount]['alttext']), 
                                                             'imageremoteurl' => $galleryinfo['remotepath'] . $gallery[$icount]['filename'], 
                                                             'imagelocalurl' => $galleryinfo['localpath'] . 'thumbs/thumbs_' . $gallery[$icount]['filename'], 
                                                             'imagedate' => $created, 
                                                             'domobj' => null, 
                                                             'loaded' => false
                                                            );
                             
                            $icount++;
                             
                            if($tpl > 2){
                                $meta = unserialize($gallery[$icount]['meta_data']);
                                $pagegallery_piece['images'][] = $gallery[$icount]['pid'];
                                $pagegallery['images'][] = array(
                                                                 'id' => $gallery[$icount]['pid'], 
                                                                 'title' => trim($gallery[$icount]['alttext']), 
                                                                 'imageremoteurl' => $galleryinfo['remotepath'] . $gallery[$icount]['filename'], 
                                                                 'imagelocalurl' => $galleryinfo['localpath'] . 'thumbs/thumbs_' . $gallery[$icount]['filename'], 
                                                                 'imagedate' => $created, 
                                                                 'domobj' => null, 
                                                                 'loaded' => false
                                                                );
                                $icount++;
                            }
                            
                            if($tpl > 6){
                                $meta = unserialize($gallery[$icount]['meta_data']);
                                $pagegallery_piece['images'][] = $gallery[$icount]['pid'];
                                $pagegallery['images'][] = array(
                                                                 'id' => $gallery[$icount]['pid'], 
                                                                 'title' => trim($gallery[$icount]['alttext']), 
                                                                 'imageremoteurl' => $galleryinfo['remotepath'] . $gallery[$icount]['filename'], 
                                                                 'imagelocalurl' => $galleryinfo['localpath'] . 'thumbs/thumbs_' . $gallery[$icount]['filename'], 
                                                                 'imagedate' => $created, 
                                                                 'domobj' => null, 
                                                                 'loaded' => false
                                                                );
                                $icount++;
                            }
                            $pagegallery['pieces'][] = $pagegallery_piece;
                             
                            $rem = $imgs - $icount;
                        }
                        
                        $response['system_status'] = 'OK';
                        $response['pagegallery'] = $pagegallery;
                    } else {
                        $response['system_status'] = 'ERROR';
                        $response['exeption'] = 'Gallery not found.';
                    }
                } else {
                    $response['system_status'] = 'ERROR';
                    $response['exeption'] = 'No valid gallery id provided.';
                }
                
                //Echo response
                header("Content-Type: text/javascript");
				echo json_encode($response);
                break;
		}
	}
	exit;
}
?>