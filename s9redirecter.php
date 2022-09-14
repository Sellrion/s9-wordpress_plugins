<?php
/*
	Plugin name: S9 Redirects
	Plugin URI: http://school9-nt.ru
	Description: Manages content redirects on a site
	Version: 1.0.3
	Author: Anton Koroteev
	Author URI: http://school9-nt.ru
*/

//Setup environment
if(is_admin()){
	//Add settings page
	add_action('admin_menu',
	function() {
		add_options_page('Перенаправления', 'Перенаправления', 'manage_options', 'redirects', 'execute_redirecterAdminEnvironment');
	});
} else execute_redirecterFrontendEnvironment();

//ADMIN ENVIRONMENT
function execute_redirecterAdminEnvironment(){
	//TODO here
	
	//Building html
	$htmlAdminEnvironment = '<div class="wrap">';
	$htmlAdminEnvironment .= '<h2>Настройка перенаправлений</h2>'; 
	$htmlAdminEnvironment .= '<p>Перенаправления позволяют переадресовывать браузеры пользователей на другие URL в случае если вы поменяли расположение контента на своем сайте и не хотите, чтобы посетители, воспользовавшись старыми ссылками, получали ошибку 404.</p>';
	
	//Print html
	echo $htmlAdminEnvironment . '</div>';
}

//FRONTEND ENVIRONMENT
function execute_redirecterFrontendEnvironment(){
	//Database object
	global $wpdb;
	
	//Prepare request uri
	$prot = is_ssl() ? 'https' : 'http';
	$request = str_replace(array($prot . '://school9-nt.ru', $prot . '://www.school9-nt.ru'), '', $_SERVER['REQUEST_URI']);
	if($request[0] != '/') $request = '/' . $request; //Leading slash
	if($request[strlen($request) - 1] != '/') $request .= '/'; //Trailing slash
	
	//Attempt to find request uri in database
	$dbrequests = $wpdb->get_results($wpdb->prepare("SELECT redirect_target, http_status_code FROM site_redirects 
													WHERE redirect_source = %s 
													AND isactive = 1 
													ORDER BY date_added DESC", $request),
									ARRAY_A);
	
	//If we have records in DB, execute redirect
	//That's all :)
	if(count($dbrequests) > 0){
		header("Location: " . $dbrequests[0]['redirect_target'], true, $dbrequests[0]['http_status_code']);
		exit;
	} else unset($request, $dbrequests);
}
?>