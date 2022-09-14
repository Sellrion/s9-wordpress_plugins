<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
    <body>
        <strong>ОБРАТНАЯ СВЯЗЬ SCHOOL9-NT.RU</strong><br />
        -----------------------------------------------------------<br />
        Пользователь <strong><?php echo $templatedata['ms_givenname']; ?></strong> отправил сообщение следующего содержания:<br /><br />
        E-mail для ответа: <?php echo $templatedata['ms_givenmail']; ?><br />
        Тема сообшения: <?php if($templatedata['ms_chosentheme'] != (string)count($ms_themes)) echo $ms_themes[$templatedata['ms_chosentheme']][0]; else echo $templatedata['ms_giventheme']; ?>
        <br />
        Контактный телефон отправителя: <?php if($templatedata['ms_givenphone'] != '') echo  $templatedata['ms_givenphone']; else echo '[не указан]'; ?>
        <br />
        <?php if(isset($templatedata['pc'])): ?>
        Имя компьютера: <?php echo $templatedata['pc']; ?>
        <br />
        <?php endif; ?>
		Сообщению присвоен уникальный идентификатор (пользовательский): <?php echo $ticket; ?>
        <br />
        Сообщению присвоен уникальный идентификатор (сервисный): <?php echo $pin; ?>
        <br /><br />
        Содержание сообщения:<br />
        <?php echo $templatedata['ms_giventext']; ?><br /><br />
        -----------------------------------------------------------<br />
        Убедительная просьба отреагировать на запрос <strong>КАК МОЖНО БЫСТРЕЕ.</strong>
    </body>
</html>