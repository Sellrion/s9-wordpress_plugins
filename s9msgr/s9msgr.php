<?php
/*
	Plugin name: S9 Feedback Messenger
	Plugin URI: https://school9-nt.ru
	Description: Provides functionality for feedback messaging
	Version: 3.2.1
	Author: Anton Koroteev
	Author URI: https://school9-nt.ru
*/

//SETUP ENVIRONMENT
if(is_admin()){
	//Add settings page
	add_action('admin_menu',
	function() {
		add_options_page('Обратная связь', 'Обратная связь', 'manage_options', 's9msgr_options', 'execute_s9msgrAdminEnvironment');
	});
} else add_shortcode('s9msgr', 'S9_Feedback_Messenger');

//EXECUTE ADMIN ENVIRONMENT
function execute_s9msgrAdminEnvironment(){
	echo 'Under construction :(';
}

use PHPMailer\PHPMailer\PHPMailer;

//MAIN SCRIPT
function S9_Feedback_Messenger(){
	//WP database object
	global $wpdb;
	
	//Main paths
	$IMAGESFOLDER = $_SERVER['DOCUMENT_ROOT'] . '/files/s9msgrcache/capchas/';
	$FILESFOLDER = $_SERVER['DOCUMENT_ROOT'] . '/files/s9msgrcache/attachments/';
	$MESSAGEMAXLENGTH = 65535;
	
	//Clean database from old capcha records
	$c_time = current_time('timestamp') - 900;
	$oldrecs = $wpdb->get_results($wpdb->prepare("SELECT * FROM site_fback_tchallenges 
												WHERE tc_date <= %d 
												OR tc_used = 1", $c_time), 
									ARRAY_A);
	for($i = 0;$i < count($oldrecs);$i++){
		if(file_exists($IMAGESFOLDER . $oldrecs[$i]['tc_image'])) unlink($IMAGESFOLDER . $oldrecs[$i]['tc_image']);
		if($oldrecs[$i]['tc_givenfile'] != ''){
			if(file_exists($FILESFOLDER . $oldrecs[$i]['tc_givenfile'])) unlink($FILESFOLDER . $oldrecs[$i]['tc_givenfile']);
		}
	}
	
	//Memory saving
	unset($oldrecs);
	if($i > 0) $wpdb->query($wpdb->prepare("DELETE FROM site_fback_tchallenges 
											WHERE tc_date <= %d 
											OR tc_used = 1", $c_time));
	
	//rebuildCounters();
	
	//Mail themes
	$ms_themes = array(
						'1' => array('Вопрос/обращение к администрации школы', 0, 'ADM', true),
						'2' => array('Предложение сотрудничества', 1, 'ADV', true),
						'3' => array('Сообщение об ошибке на сайте', 2, 'WEB', false),
						'4' => array('Проблема с системой СГО', 2, 'EJR', false),
						'5' => array('Проблема с системой Е-Услуги', 2, 'ESV', false),
                        '6' => array('Запрос технической поддержки ЦОС', 2, 'ISR', false),
						'7' => array('Другое', 0, 'MSC', true)
	                   );
    
    $HEADTEACHERMAIL = 'esokolova1970@mail.ru';
    
    //Mails
    $ms_mails = array(
                        array('pochta@school9-nt.ru', 'adasd*&-as247dsdwedvgj'), 
                        array('contract@school9-nt.ru', 'jcnCH95t24cn-*shr$'), 
                        array('webmaster@school9-nt.ru', 'sDAS-=125^*(6-&BW__254783sasadw&&*(y')
                    );
	
	//Set up default settings
	$templatedata = array(
							'c_maxlength' => 50,
							't_maxlength' => 100,
							'att_maxfilesize' => 1048576,
							'imagepath' => '',
							'image_h' => null,
							'image_w' => null,
							'filetypes' => array('application/x-zip-compressed', 'image/jpeg', 'image/png', 'image/x-png', 'image/pjpeg'),
							'fileextentions' => array('jpeg', 'jpg', 'png', 'zip'), 
                            'prefilled' => null
							);
	
	$ms_process_status = array(
							   '0' => 'Закрыто',
							   '1' => 'В обработке', 
							   '2' => 'Получено', 
							   '3' => 'Отправлено'
							  );
	
	//Message types and stats
	$ms_stats = $wpdb->get_results($wpdb->prepare("SELECT statname, statslug, stat_value FROM site_feedback_stats"), ARRAY_A);
	
	//Message types
	$ms_types = array();
	$templatedata['stats'] = array();
	for($i = 0; $i < count($ms_stats); $i++){
		$ms_types[$ms_stats[$i]["statname"]] = $ms_stats[$i]["statslug"];
		$templatedata['stats'][] = array(
										 'slug' => $ms_stats[$i]["statslug"], 
										 'value' => $ms_stats[$i]["stat_value"]
										 );
	}
	
	//Try to take real ip
	$uip = '';
	if (isset($_SERVER['HTTP_CLIENT_IP'])) $uip = $_SERVER['HTTP_CLIENT_IP']; else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $uip = $_SERVER['HTTP_X_FORWARDED_FOR']; else $uip = $_SERVER['REMOTE_ADDR'];
		
	
	//Build idhash
	$idhash = md5($_SERVER['HTTP_USER_AGENT'] . $uip);
	
	//User is loading the sendmessage page
	if(!isset($_POST['ms_session'])){
		//We have a new user so display the contact form
		//Get sessionhash
		$ms_hash = md5(uniqid(microtime(), true));
		//Build captcha image
        require_once("c-builder/c_builder.php");
        $c_image = new SimpleCaptcha();
        
		$templatedata['imagepath'] = $c_image->c_savepath . $c_image->c_imagename;
		$templatedata['image_h'] = $c_image->height;
		$templatedata['image_w'] = $c_image->width;
		$templatedata['sinput_p'] = $c_image->width + 15;
		$templatedata['ms_session'] = $ms_hash;
		//Write new session
		$wpdb->insert(
						'site_fback_tchallenges', 
						array(
								'sessionhash' => $ms_hash,
								'idhash' => $idhash,
								'tc_solve' => $c_image->c_text,
								'tc_image' => $c_image->c_imagename,
								'tc_date' => current_time('timestamp'),
								'tc_used' => 0
						),
						array('%s', '%s', '%s', '%s', '%d', '%d')
					);
        
        if(isset($_REQUEST['prefilled'])){
            $templatedata['prefilled'] = json_decode(stripslashes($_REQUEST['prefilled']), true);
            foreach($templatedata['prefilled'] as $prefilled_field_name => $prefilled_field_value){
                $templatedata[$prefilled_field_name] = $prefilled_field_value;
            }
            
            if(isset($templatedata['prefilled']['ms_giventheme'])) $templatedata['ms_chosentheme'] = '7';
        }
			
	} else {
		$s = $_POST['ms_session'];
		
		$erecs = $wpdb->get_results($wpdb->prepare("SELECT * FROM site_fback_tchallenges 
													WHERE sessionhash = %s 
													AND idhash = %s 
													AND tc_used = 0 
													ORDER BY tc_date DESC", $s, $idhash),
									ARRAY_A);
		
		//User is searching a feedback ticket
		if(isset($_POST["ms_giventicket"])){
			if($erecs){
				$tn = $_POST["ms_giventicket"];
				
				if(preg_match('#^[A-F0-9]{32}$#', $tn)){
					$sf = $wpdb->get_results($wpdb->prepare("SELECT * FROM site_feedbacks 
															 WHERE feedback_admpin = %s", $tn),
											 ARRAY_A);
					
					if(!$sf){
                        $message = 'Обращения с таким уникальным идентификатором не найдено. Пожалуйста, проверьте правильность ввода.';
                        
                        ob_start();
            
                        include('tpl/tickets.php');
			
                        return ob_get_clean();
					} else {
						if(isset($_POST['mst_action']) && $_POST['mst_action'] == 'save'){
							$newtype_post = htmlspecialchars($_POST['mst_type']);
							$newstatus_post = htmlspecialchars($_POST['mst_status']);
							
							$newtype = '';
							$newstatus = 0;
							
							foreach($ms_process_status as $statname => $statvalue){
								if($statvalue == $newstatus_post){
									$newstatus = intval($statname);
									break;
								}
							}
							
							foreach($ms_types as $typename => $typevalue){
								if($typevalue == $newtype_post){
									$newtype = intval($typename);
									break;
								}
							}
							
							$wpdb->update(
										  'site_feedbacks',
										  array('feedback_status' => $newstatus,  
											   	'feedback_type' => $newtype
											   ), 
										  array(
												'feedback_admpin' => $tn
										 	   ),
										  array('%d', '%s'), array('%s')
										 );
							
							//Modify stats
							if($sf[0]['feedback_type'] != $newtype){
								changeStat($sf[0]['feedback_type'], 0);
								changeStat($newtype, 1);
							}
							
							//Send email
							
                            $message = 'Все изменения были успешно сохранены.';
                        
                            ob_start();
            
                            include('tpl/tickets.php');
			
                            return ob_get_clean();
						} else {
							$templatedata["ms_session"] = $s;
							$templatedata["ms_date"] = $sf[0]["feedback_date"];
							$templatedata["ms_text"] = $sf[0]["feedback_text"];
							$templatedata["ms_theme"] = $sf[0]["feedback_giventheme"];
							$templatedata["ms_name"] = $sf[0]["feedback_givenname"];
							$templatedata["ms_status"] = $sf[0]["feedback_status"];
							$templatedata["ms_type"] = $sf[0]["feedback_type"];
							$templatedata["ms_giventicket"] = $tn;

							$is_service_access = true;
                            
                            ob_start();
            
                            include('tpl/tickets.php');
			
                            return ob_get_clean();
						}
					}
				} else {
					if(!preg_match('#^(S9)[0-9]{10}[-][A-Z]{4}((ADM)|(ADV)|(WEB)|(EJR)|(ESV)|(MSC))$#', $tn)){
						$message = 'Неверный формат уникального идентификатора. Пожалуйста введите корректный 20 значный уникальный идентификатор обращения.';
                        
                        ob_start();
            
                        include('tpl/tickets.php');
			
                        return ob_get_clean();
					}

					$sf = $wpdb->get_results($wpdb->prepare("SELECT * FROM site_feedbacks 
															 WHERE feedback_ticket = %s", $tn),
											 ARRAY_A);

					if(!$sf){
						$message = 'Обращения с таким уникальным идентификатором не найдено. Пожалуйста, проверьте правильность ввода.';
                        
                        ob_start();
            
                        include('tpl/tickets.php');
			
                        return ob_get_clean();
					} else {
						if(isset($_POST['mst_action']) && $_POST['mst_action'] == 'close'){
							$wpdb->update(
										  'site_feedbacks',
										  array('feedback_status' => 0),
										  array('feedback_ticket' => $tn),
										  array('%d'), 
										  array('%s')
										 );
							
							$message = 'Ваше обращение было закрыто.';
                        
                            ob_start();
            
                            include('tpl/tickets.php');
			
                            return ob_get_clean();
						} else {
							$templatedata["ms_session"] = $s;
							$templatedata["ms_date"] = $sf[0]["feedback_date"];
							$templatedata["ms_text"] = $sf[0]["feedback_text"];
							$templatedata["ms_theme"] = $sf[0]["feedback_giventheme"];
							$templatedata["ms_name"] = $sf[0]["feedback_givenname"];
							$templatedata["ms_status"] = $sf[0]["feedback_status"];
							$templatedata["ms_type"] = $sf[0]["feedback_type"];
							$templatedata["ms_giventicket"] = $tn;
                            
                            $is_service_access = false;
                            
                            ob_start();
            
                            include('tpl/tickets.php');
			
                            return ob_get_clean();
						}
					}
				}
			}
		}
		
		//User is trying to send a feedback, so lets check it out
		$message = '';
		$p = false;
		$timenow = current_time('timestamp');
		//When user submits a form faster than any human can do, it's not funny
		if(!$erecs || $timenow - $erecs[0]['tc_date'] < 10){
			$message = '<li>Вам необходимо заново пройти проверку изображением. Пожалуйста, введите еще раз цифры с картинки в текстовое поле и попробуйте заново создать обращение.</li>';
			$p = true;
		}
		foreach($_POST as $postname => $postval){
			$postval = trim($postval);
			switch($postname){
				case 'ms_givenname':
					if($postval == '' && $_POST['ms_chosentheme'] != '3'){
						$message .= '<li>Вы не указали своего имени.</li>';
						$p = true;
					} else if(strlen($postval) > $templatedata['c_maxlength']){
						$message .= '<li>Вы указали слишком длинное имя. Максимальная длина имени - ' . $templatedata['c_maxlength'] . ' символов.</li>';
						$p = true;
					}
					break;
				case 'ms_giventext':
					if($postval == ''){
						$message .= '<li>Вы не ввели текст обращения.</li>';
						$p = true;
					} else if(strlen($postval) > $MESSAGEMAXLENGTH){
						$message .= '<li>Извините, текст введенного вами обращения слишком длинный. Попытайтесь его сократить.</li>';
						$p = true;
					}
					break;
				case 'ms_givenmail':
					if($postval == ''){
						$message .= '<li>Вы не ввели свой e-mail для ответа.</li>';
						$p = true;
					} else if(!preg_match('#^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$#', $postval)){
						$message .= '<li>E-mail введен неверно. Укажите e-mail для ответа в формате proverka@primer.ru</li>';
						$p = true;
					}
					break;
				case 'ms_givenphone':
					if($postval != '' && !preg_match('#^((8|\+7)[\- ]?)?(\(?\d{3,4}\)?[\- ]?)?[\d\- ]{7,10}$#', $postval)){
						$message .= '<li>Номер телефона может состоять только из цифр от 0 до 9 и символов ( ) + -</li>';
						$p = true;
					}
					break;
				case 'ms_chosentheme':
					$postval = intval($postval);
					if($postval == 0 || !($postval >= 0 && $postval <= count($ms_themes))){
						$message .= '<li>Необходимо выбрать тему из предложенных.</li>';
						$p = true;
					} else if($postval == count($ms_themes)) {
						if(trim($_POST['ms_giventheme']) == ''){
							$message .= '<li>Необходимо указать свою тему или выбрать из предложенных тем.</li>';
							$p = true;
						} else if(strlen(trim($_POST['ms_giventheme'])) > $templatedata['t_maxlength']){
							$message .= '<li>Вы указали слишком длинную тему сообщения. Максимальная длина темы - ' . $templatedata['t_maxlength'] . ' символов.</li>';
							$p = true;
						}
					}
					break;
				case 'ms_givensolve':
					if(isset($erecs[0]) && ($postval == '' || $postval != $erecs[0]['tc_solve'])){
						$message .= '<li>Вы не прошли проверку изображением. Введите правильно числа с изображения в текстовое поле.</li>';
						$p = true;
					}
					break;
			}
			if($postname != 'ms_givenfile'){
                if($postname == 'ms_prefilled'){
                    $templatedata['prefilled'] = json_decode(str_replace("'", '"', stripslashes($postval)), true);
                } else {
                    $templatedata[$postname] = htmlspecialchars($postval, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
		}
			
		//Check out uploaded file
		$pf = false;
		if(isset($erecs[0])){ //We recieve a file only when a valid session exists
			if($erecs[0]['tc_givenfile'] == ''){ //If there is no information about uploaded file in session
				if(is_uploaded_file($_FILES['ms_givenfile']['tmp_name'])){ //If user has uploaded a file
					//Check out file size
					if($_FILES['ms_givenfile']['size'] > $templatedata['att_maxfilesize']){
						$message .= '<li>Загруженный вами файл слишком велик. Допустимый размер файла: ' . round($templatedata['att_maxfilesize'] / 1048576, 1) . 'Мб</li>';
						$pf = true;
					}
					
					$fname = explode('.', $_FILES['ms_givenfile']['name']);
					$fext = $fname[count($fname) - 1];
					if(!in_array($_FILES['ms_givenfile']['type'], $templatedata['filetypes']) || !in_array($fext, $templatedata['fileextentions'])){
						$message .= '<li>Неизвестный формат загруженного файла.</li>';
						$pf = true;
					}
					
					if(!$pf){
						$fn = md5($_FILES['ms_givenfile']['tmp_name']) . '.' . $fext;
						move_uploaded_file($_FILES['ms_givenfile']['tmp_name'], $FILESFOLDER . $fn);
						$templatedata['ms_givenfile'] = array(
																'name' => $fn,
																'type' => $_FILES['ms_givenfile']['type'],
																'realname' => $_FILES['ms_givenfile']['name'],
																'size' => $_FILES['ms_givenfile']['size']
																);
					}
				}
			} else { //If file has been uploaded, get it
				$templatedata['ms_givenfile'] = array(
														'name' => $erecs[0]['tc_givenfile'],
														'type' => $erecs[0]['tc_givenfiletype'],
														'realname' => stripslashes($erecs[0]['tc_givenfile_realname']),
														'size' => $erecs[0]['tc_givenfilesize']
														);
			}
		}
			
		//We have errors
		if($p || $pf){
			//Now we need to give another capcha test
            require_once("c-builder/c_builder.php");
            $newc = new SimpleCaptcha();
			$templatedata['imagepath'] = $newc->c_savepath . $newc->c_imagename;
			$templatedata['image_h'] = $newc->height;
			$templatedata['image_w'] = $newc->width;
			$templatedata['sinput_p'] = $newc->width + 15;
				
			//And update existing session
			if(isset($erecs[0])){
				if(!isset($templatedata['ms_givenfile'])){
					$wpdb->update(
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
				} else {
					$wpdb->update(
									'site_fback_tchallenges',
									array(
											'tc_image' => $newc->c_imagename,
											'tc_solve' => $newc->c_text,
											'tc_givenfile' => $templatedata['ms_givenfile']['name'],
											'tc_givenfiletype' => $templatedata['ms_givenfile']['type'],
											'tc_givenfile_realname' => $templatedata['ms_givenfile']['realname'],
											'tc_givenfilesize' => $templatedata['ms_givenfile']['size']
											),
									array(
											'sessionhash' => $s,
											'idhash' => $idhash
											),
									array('%s', '%s', '%s', '%s', '%s', '%d'),
									array('%s', '%s')
									);
				}
			} else {
				//If no session, create a new one
				$s = md5(uniqid(microtime(), true));
				if(!isset($templatedata['ms_givenfile'])){
					$wpdb->insert(
									'site_fback_tchallenges', 
									array(
											'sessionhash' => $s,
											'idhash' => $idhash,
											'tc_solve' => $newc->c_text,
											'tc_image' => $newc->c_imagename,
											'tc_date' => $timenow,
											'tc_used' => 0
									),
									array('%s', '%s', '%s', '%s', '%d', '%d'));
				} else {
					$wpdb->insert(
									'site_fback_tchallenges', 
									array(
											'sessionhash' => $s,
											'idhash' => $idhash,
											'tc_solve' => $newc->c_text,
											'tc_image' => $newc->c_imagename,
											'tc_givenfile' => $templatedata['ms_givenfile']['name'],
											'tc_givenfiletype' => $templatedata['ms_givenfile']['type'],
											'tc_givenfile_realname' => $templatedata['ms_givenfile']['realname'],
											'tc_givenfilesize' => $templatedata['ms_givenfile']['size'],
											'tc_date' => $timenow,
											'tc_used' => 0
									),
									array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d'));
				}
				$templatedata['ms_session'] = $s;
			}
				
			//Add an error message
			$message = 'При создании вашего обращения возникли следующие ошибки:<ol>' . $message . '</ol>';
		} else {
			//Everything's fine, so send the message
			//Message template
			if(!isset($templatedata['ms_givenname'])) $templatedata['ms_givenname'] = '[Имя не указано]';
			$templatedata['ms_giventext'] = preg_replace('#[\r\n]+#i', '<br />', $templatedata['ms_giventext']);
            if(isset($_POST['pc'])) $templatedata['pc'] = $_POST['pc'];
			$ticket = 'S9' . $timenow . '-' . generateTicketSalt() . $ms_themes[$templatedata['ms_chosentheme']][2];
			$pin = strtoupper(md5($ticket));
			
            ob_start();
            
            include('tpl/themessage.php');
			
            $email = ob_get_clean();
            
            if($templatedata['ms_chosentheme'] != (string)count($ms_themes)) {
				$to = $ms_mails[$ms_themes[$templatedata['ms_chosentheme']][1]][0];
				$subject = $ms_themes[$templatedata['ms_chosentheme']][0];
                $password = $ms_mails[$ms_themes[$templatedata['ms_chosentheme']][1]][1];
 			} else {
				$to = "pochta@school9-nt.ru";
				$subject = $templatedata['ms_giventheme'];
                $password = $ms_mails[$ms_themes[(string)count($ms_themes)][1]][1];
			}
            
            //Mailer files
            require_once("mailer/PHPMailer.php");
            require_once("mailer/SMTP.php");
            require_once("mailer/Exception.php");
            
            //Init mailer
            $mailer = new PHPMailer();
            
            //Mailer charset and mode
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;;
            $mailer->isSMTP();
            
            //Mail server settings
            $mailer->Host = 'mail.school9-nt.ru';
            $mailer->Port = 25;
            $mailer->SMTPAuth = true;
            $mailer->Username = $to;
            $mailer->Password = $password;
            
            //Message headers
            $mailer->setFrom('nothingness@school9-nt.ru', $templatedata['ms_givenname']);
            $mailer->addReplyTo($templatedata['ms_givenmail'], $templatedata['ms_givenname']);
            $mailer->addAddress($to);
            
            //Send the copy of the mail to the headteacher's private mailbox
            if($ms_themes[$templatedata['ms_chosentheme']][3]){
                $mailer->addAddress($HEADTEACHERMAIL);
            }
            
            $mailer->isHTML(true);
            
            //Message
            $mailer->Subject = 'SCHOOL9-NT.RU: ОБРАТНАЯ СВЯЗЬ - ' . $subject;
            $mailer->Body = $email;
            $mailer->AltBody = '';
            
            //Attachment
            if(isset($templatedata['ms_givenfile'])){
                $mailer->addAttachment($FILESFOLDER . $templatedata['ms_givenfile']['name'], 'Прикрепленный файл');
            }
			
			//And send mail!
			if($mailer->send()){
				//Remember this message
				$wpdb->insert(
									'site_feedbacks', 
									array(
											'feedback_ticket' => $ticket,
											'feedback_text' => $templatedata['ms_giventext'],
											'feedback_date' => $timenow,
											'feedback_takenip' => $uip,
											'feedback_giventheme' => $subject,
											'feedback_givenname' => $templatedata['ms_givenname'],
											'feedback_givenemail' => $templatedata['ms_givenmail'],
											'feedback_givenfilename' => (isset($templatedata['ms_givenfile'])) ? $templatedata['ms_givenfile']['realname'] : '',
											'feedback_givenfilesize' => (isset($templatedata['ms_givenfile'])) ? $templatedata['ms_givenfile']['size'] : -1, 
											'feedback_status' => 3, 
											'feedback_type' => 0, 
											'feedback_admpin' => $pin
									),
									array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s')
                            );
				
				//Change stats
				changeStat('total', 1);
				changeStat('0', 1);
				
				//Use the session
				$wpdb->update(
								'site_fback_tchallenges',
								array('tc_used' => 1),
								array(
										'sessionhash' => $s,
										'idhash' => $idhash
										),
								array('%d'), array('%s', '%s')
								);
				
				$message = 'Ваше обращение было успешно создано. Вы получите ответ на ваш запрос в течении одного <strong>рабочего</strong> дня.<br /><br />Вашему обращению присвоен уникальный идентификационный номер: <br /><br /><div style="text-align: center;font-weight: bold;">' . $ticket . '</div><br />По этому номеру вы сможете отследить состояние обработки запроса на странице обратной связи.<br /><br />Спасибо за обращение в МАОУ СОШ №9.';
			} else {
				$message = 'Не удалось создать обращение. Пожалуйста попробуйте позднее.<br /><br />' . $mailer->ErrorInfo;
			}
			
			$templatedata = null;
		}
	}
	
    ob_start();
    
    include('tpl/themessenger.php');
	
    return ob_get_clean();
}

function generateTicketSalt(){
	$tsalt = '';
	for($i = 0; $i < 4; $i++){
		$tsalt .= chr(rand(65, 90));
	}
	
	return strtoupper($tsalt);
}

function rebuildCounters(){
	//WP database object
	global $wpdb;
	
	$ftypes_db = $wpdb->get_results($wpdb->prepare("SELECT feedback_type FROM site_feedbacks"), ARRAY_A);
	
	$fstats['total'] = count($ftypes_db);
	
	for($i = 0; $i < $fstats['total']; $i++){
		$fstats[$ftypes_db[$i]['feedback_type']]++;
	}
	
	foreach($fstats as $fstatname => $fstatval){
		$wpdb->update(
						'site_feedback_stats',
						array(
								'stat_value' => $fstatval
								),
						array(
								'statname' => $fstatname
								),
						array('%d'),
						array('%s')
						);
	}
}

function changeStat($statname, $direction){
	//WP database object
	global $wpdb;
	
	$querystring = "UPDATE site_feedback_stats 
					SET stat_value=" . (($direction == 1) ? "stat_value+1" : "stat_value-1") . " 
					WHERE statname='" . $statname . "'";
	
	$wpdb->query($querystring);
}
?>