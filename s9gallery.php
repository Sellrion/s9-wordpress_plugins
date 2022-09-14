<?php
/*
	Plugin name: S9 Gallery Tools
	Plugin URI: http://school9-nt.ru
	Description: Usefull tools to manage a galleries on a site
	Version: 1.0
	Author: Anton Koroteev
	Author URI: http://school9-nt.ru
*/

//SETUP ENVIRONMENT
if(is_admin()){
	//Add settings page
	add_action('admin_menu',
	function() {
		add_options_page('Инструменты фотогралереи', 'Инструменты фотогралереи', 'manage_options', 'gtools_options', 'execute_s9gtoolsAdminEnvironment');
	});
}

//ADMIN ENVIRONMENT
function execute_s9gtoolsAdminEnvironment(){
    global $wpdb;
    
    if(isset($_POST['backgroundMosaic_exluded_galleryids'])){
        $retval = $wpdb->update(
				                'site_ngg_options',
								array('value' => $_POST['backgroundMosaic_exluded_galleryids']),
                                array('name' => 'backgroundMosaic_exluded_galleryids'), 
								array('%s'), 
                                array('%s')
				                );
    }
    
    if(isset($_POST['siteGallery_exluded_galleryids'])){
        $retval = $wpdb->update(
				                'site_ngg_options',
								array('value' => $_POST['siteGallery_exluded_galleryids']),
                                array('name' => 'siteGallery_exluded_galleryids'), 
								array('%s'), 
                                array('%s')
				                );
    }
    
    //Building html
	$htmlAdminEnvironment = '<div class="wrap">';
	$htmlAdminEnvironment .= '<h2>Инструменты фотогалереи</h2>';
    $htmlAdminEnvironment .= '<h3>Список галерей, которые не будут участвовать в генерации фоновой мозаики сайта:</h3>';
    $htmlAdminEnvironment .= '<form method="post" action="">';
    
    $excludedgalleriesids = "";
    $nggoptions = $wpdb->get_results($wpdb->prepare("SELECT * FROM site_ngg_options"), ARRAY_A);
    for($i = 0; $i < count($nggoptions); $i++){
        if($nggoptions[$i]['name'] == 'backgroundMosaic_exluded_galleryids'){
            $excludedgalleriesids = $nggoptions[$i]['value'];
            break;
        }
    }
    
    $htmlAdminEnvironment .= '<textarea name="backgroundMosaic_exluded_galleryids" rows="10" cols="70">' . $excludedgalleriesids . '</textarea><br /><input type="submit" value="Обновить" /></form>';
    $htmlAdminEnvironment .= '<h3>Список галерей, которые не будут отображаться на главной странице фотогалереи сайта:</h3>';
    $htmlAdminEnvironment .= '<form method="post" action="">';
    
    $excludedgalleriesids = "";
    $nggoptions = $wpdb->get_results($wpdb->prepare("SELECT * FROM site_ngg_options"), ARRAY_A);
    for($i = 0; $i < count($nggoptions); $i++){
        if($nggoptions[$i]['name'] == 'siteGallery_exluded_galleryids'){
            $excludedgalleriesids = $nggoptions[$i]['value'];
            break;
        }
    }
    
    $htmlAdminEnvironment .= '<textarea name="siteGallery_exluded_galleryids" rows="10" cols="70">' . $excludedgalleriesids . '</textarea><br /><input type="submit" value="Обновить" /></form>';
	
	//Print html
	echo $htmlAdminEnvironment . '</div>';
}
?>