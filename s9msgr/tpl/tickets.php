                <?php if($message): ?>
                    <div class="s9msgr-error"><?php echo $message; ?></div>
                <?php else: ?>
                    <form id="s9msgr-ticket" method="post" action="/sendmessage">
                        <input id="ms_session" type="hidden" name="ms_session" value="<?php echo $templatedata['ms_session']; ?>" />
	                    <input id="ms_giventicket" type="hidden" name="ms_giventicket" value="<?php echo $templatedata['ms_giventicket']; ?>" />
                        <?php if($is_service_access): ?>
                            <input id="mst_action" type="hidden" name="mst_action" value="save" />
                        <?php else: ?>
                            <input id="mst_action" type="hidden" name="mst_action" value="close" />
                        <?php endif; ?>
                        <div style="line-height: 20px;">
                            <strong>Дата обращения: </strong><?php echo date('d.m.Y @ H:i', $templatedata["ms_date"]); ?><br />
	                        <strong>Тема: </strong><?php echo $templatedata["ms_theme"]; ?><br />
	                        <strong>Имя автора: </strong><?php echo $templatedata["ms_name"]; ?><br />
	                        <strong>Тип (определяется нашими специалистами): </strong>
                            <?php if($is_service_access): ?>
                                <select class="s9msgr-select" name="mst_type">
                                    <?php foreach($ms_types as $typename => $typevalue): 
                                            if($typename === 'total') continue; ?>
                                            <option name="<?php echo $typename; ?>"<?php if($typename == $templatedata["ms_type"]): ?>  selected="selected"<?php endif; ?>><?php echo $typevalue; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <?php echo $ms_types[$templatedata["ms_type"]]; ?>
                            <?php endif; ?>
                            <br />
                            <strong>Статус обращения: </strong>
                            <?php if($is_service_access): ?>
                                <select class="s9msgr-select" name="mst_status">
                                    <?php foreach($ms_process_status as $statusname => $statusvalue): ?>
                                        <option name="<?php echo $statusname; ?>"<?php if($statusname == $templatedata["ms_status"]): ?> selected="selected"<?php endif; ?>><?php echo $statusvalue; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <?php echo $ms_process_status[$templatedata["ms_status"]]; ?>
                            <?php endif; ?>
                            <br />
                            <strong>Текст обращения: </strong><br /><?php echo $templatedata["ms_text"]; ?>
                            <br />
                            <?php if($is_service_access): ?>
                                <div style="padding: 5px;margin-top: 10px;">
                                    <div style="float: left;">
                                        <input id="mst_sendnote" type="checkbox" name="mst_sendnote" value="mst_sendnote" checked="checked"><label for="mst_sendnote"> Отправить извещение об изменениях автору обращения</label>
                                    </div>
                                    <div style="text-align: right;">
                                        <input id="mst_close" style="padding: 3px;" type="submit" name="mst_save" value="Сохранить изменения" />
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            <?php else: ?>
                                <?php if($templatedata["ms_status"] != '0'): ?>
                                    <div style="text-align: right;padding: 5px;">
                                        <input id="mst_close" style="padding: 3px;" type="submit" name="mst_close" value="Закрыть обращение" />
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>                        