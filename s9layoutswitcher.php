<?php
/*
	Plugin name: S9 Layout Switcher
	Plugin URI: http://school9-nt.ru
	Description: Allows users manually switch a layout of the site
	Version: 1.1.0
	Author: Anton Koroteev
	Author URI: http://school9-nt.ru
*/

if(!function_exists('linkRemove_Request')){
	require_once('s9pluginfunctions.php');	
}

//Setup environment
if(is_admin()){
	//Nothing to do here
} else {
	add_filter('stylesheet', 'execute_SwitchLayout');
	add_filter('template', 'execute_SwitchLayout');
}

//FRONTEND ENVIRONMENT
function execute_SwitchLayout(){
	//Default layout
	$newlayout = 'school9_v3';
	
	if(isset($_REQUEST['layout'])){
		//Template change requested
        $cookielayout = "";
		switch($_REQUEST['layout']){
			case 'normal': 
                $newlayout = 'school9_v3';
                $cookielayout = 'normal';
                break;
			case 'visually-impaired': 
                $newlayout = 'school9_vi';
                $cookielayout = 'visually-impaired';
                break;
			default: 
                $newlayout = 'school9_v3';
                $cookielayout = 'normal';
                break;
		}
		
		if(@setcookie('layout', $cookielayout, time() + 32140800)){
			$used_protocol = (!is_ssl()) ? 'http://' : 'https://';
			$request_clean = linkRemove_Request($_SERVER['REQUEST_URI'], 'layout=');
			
			header("Location: " . $used_protocol . $_SERVER['HTTP_HOST'] . $request_clean, true, 302);
			exit;
		}
	} else {
		//Get template for this user
		if(isset($_COOKIE['layout'])){
			switch($_COOKIE['layout']){
				case 'normal': $newlayout = 'school9_v3'; break;
				case 'visually-impaired': $newlayout = 'school9_vi'; break;
				default: $newlayout = 'school9_v3'; break;
			}	
		}
	}
	
	//Set the layout
	return $newlayout;
}
?>