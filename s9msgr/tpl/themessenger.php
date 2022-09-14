                <div id="s9msgr-container">
                    <?php if($message): ?>
                            <div <?php if(!$templatedata): ?>class="s9msgr-message"<?php else: ?>class="s9msgr-error"<?php endif; ?>><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if(!$templatedata): ?>
                </div>
                    <?php else: ?>
                        <form id="s9msgr-sticket" method="post" action="/sendmessage">
                            <div class="s9msgr-options_container" style="flex-wrap: nowrap;">
                                <div style="width: 90%;"><input id="ms_giventicket" type="text" name="ms_giventicket" maxlength="32" placeholder="Введите номер обращения для отслеживания*" required="required" /></div>
                                <div style="padding-left: 20px;"><input id="st_submit" type="button" value="Поиск" /></div>
                            </div>
                            <div id="ms_giventicket-error" class="s9msgr-inputerror" style="display: none;">Пожалуйста, введите правильный номер обращения. Он был передан вам при создании обращения или в электронном письме.</div>
                            <input id="ms_session" type="hidden" name="ms_session" value="<?php echo $templatedata['ms_session']; ?>" />
                        </form>
                        <br />
                        <h4>Или создайте новое обращение:</h4>
                        <?php if($templatedata['prefilled']): ?>
                            <div class="s9msgr-message"><strong>Внимание!</strong> Некоторые поля были заполнены автоматически и недоступны для редактирования. Пожалуйста, заполните оставшиеся поля и создайте электронное обращение.</div>
                        <?php endif; ?>
                        <form id="s9msgr-form" action="/sendmessage" method="post" name="s9msgr-form" enctype="multipart/form-data">
                            <input id="ms_givenname" type="text" name="ms_givenname" maxlength="<?php echo $templatedata['c_maxlength']; ?>" value="<?php echo $templatedata['ms_givenname']; ?>" placeholder="Введите ваше имя*" required="required"<?php if($templatedata['prefilled']['ms_givenname']): ?> disabled="disabled"<?php endif; ?> />
                            <div id="ms_givenname-error" class="s9msgr-inputerror" style="display: none;">Имя может содержать буквы английского и русского алфавитов, цифры от 0 до 9 и символы: пробел _ -</div><br /><br />
                            <input id="ms_givenmail" type="email" name="ms_givenmail" maxlength="<?php echo $templatedata['c_maxlength']; ?>" value="<?php echo $templatedata['ms_givenmail']; ?>" placeholder="Введите ваш E-Mail*" required="required"<?php if($templatedata['prefilled']['ms_givenmail']): ?> disabled="disabled"<?php endif; ?> />
                            <div id="ms_givenmail-error" class="s9msgr-inputerror" style="display: none;">Пожалуйста, правильно укажите ваш e-mail в формате proverka@example.ru</div><br /><br />
                            <input id="ms_givenphone" type="tel" name="ms_givenphone" maxlength="<?php echo $templatedata['c_maxlength']; ?>" value="<?php echo $templatedata['ms_givenphone']; ?>" placeholder="Введите ваш контактный номер телефона"<?php if($templatedata['prefilled']['ms_givenphone']): ?> disabled="disabled"<?php endif; ?> />
                            <div id="ms_givenphone-error" class="s9msgr-inputerror" style="display: none;">В номере телефона допускается использовать цифры от 0 до 9 и символы ( ) + -</div><br /><br />
                            <div class="s9msgr-options_container">
                                <div style="flex-grow: 1;flex-shrink: 1;width: 40%;"><label>Выберите тему обращения:*</label></div>
                                <div style="flex-grow: 1;flex-shrink: 1;">
                                    <select id="ms_chosentheme" name="ms_chosentheme" required="required"<?php if($templatedata['prefilled']['ms_chosentheme']): ?> disabled="disabled"<?php endif; ?>>
                                        <option value="0"></option>
                                        <?php foreach($ms_themes as $t_itemkey => $t_itemvalue): ?>
                                            <option value="<?php echo $t_itemkey; ?>"<?php if($templatedata['ms_chosentheme'] == $t_itemkey): ?> selected="selected"<?php endif; ?>><?php echo $t_itemvalue[0]; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="ms_chosentheme-error" class="s9msgr-inputerror" style="display: none;">Вы должны выбрать тему обращения</div>
                                </div>
                            </div>
                            <br /><br />
                            <div id="giventheme_row" <?php if($templatedata['ms_giventheme'] != ''): ?>style="display: block;"<?php else: ?>style="display: none;"<?php endif; ?>><input id="ms_giventheme" type="text" name="ms_giventheme" maxlength="<?php echo $templatedata['t_maxlength']; ?>" value="<?php echo $templatedata['ms_giventheme']; ?>" placeholder="Укажите свою тему обращения*"<?php if($templatedata['prefilled']['ms_giventheme']): ?> disabled="disabled"<?php endif; ?> />
                            <div id="ms_giventheme-error" class="s9msgr-inputerror" style="display: none;">Введите свою тему для обращения или выберите из предложенных выше</div><br /><br /></div>
                            <textarea id="ms_giventext" name="ms_giventext" placeholder="Введите ваше сообщение*" required="required"<?php if($templatedata['prefilled']['ms_giventext']): ?> disabled="disabled"<?php endif; ?>><?php echo $templatedata['ms_giventext']; ?></textarea>
                            <div id="ms_giventext-error" class="s9msgr-inputerror" style="display: none;">Вы не ввели сообщение</div><br /><br />
                            <?php if(!isset($templatedata['ms_givenfile'])): ?>
                                <input type="file" name="ms_givenfile" accept=".png, .jpg, .jpeg, .zip" />
                                <div style="font-size: var(--fontsize-small);margin-top: 5px;"><strong>Поддерживаемые форматы: </strong>JPG, PNG, ZIP<br /><strong>Максимальный размер файла: </strong><?php echo round($templatedata['att_maxfilesize'] / 1048576, 1) ?>Мб</div>
                            <?php else: ?>
                                <strong>Файл успешно загружен:</strong> <?php echo $templatedata['ms_givenfile']['realname']; ?>
                            <?php endif; ?>
                            <br /><br />
                            <div class="s9msgr-options_container">
                                <div id="c_image" style="flex-grow: 0;flex-shrink: 1;width: <?php echo $templatedata['image_w']; ?>px;height: <?php echo $templatedata['image_h']; ?>px;">
                                    <img src="<?php echo $templatedata['imagepath']; ?>" alt="" />
                                </div>
                                <div style="flex-grow: 0;flex-shrink: 0;width: 20px;padding-left: 10px;padding-right: 10px;">
                                    <a id="c_refreshlink" href="javascript:void(0);" title="Обновить изображение">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 318.7 401.7" style="enable-background:new 0 0 318.7 401.7;" xml:space="preserve"><g><path fill="#ffffff" d="M318.7,200.5c0-47.4-20.7-90-53.5-119.2c0,0-38.7,21.6-38.7,21.6c31,21.2,51.4,56.8,51.4,97.2c0,58.5-42.7,107.1-98.7,116.1c-1.7,0.3-3-1.7-2-3.1c5.2-7.6,17-24.6,23.4-33.9c1.2-1.7-0.7-3.8-2.5-2.9L75.2,337c-1.5,0.7-1.5,2.8,0,3.6l121.7,60.9c1.8,0.9,3.7-1.2,2.6-2.9L175.6,362c-0.8-1.3,0-2.9,1.5-3.1C256.7,350.1,318.7,282.6,318.7,200.5z "/><path fill="#ffffff" d="M122.5,119.1c-1.1,1.7,0.7,3.8,2.5,2.9l117.2-57.5l2.3-1.1c1.5-0.7,1.5-2.9,0-3.6L124.2,0.2c-1.8-0.9-3.7,1.2-2.5,2.9l23.9,35.7c0.8,1.3,0,3-1.5,3.1C63.2,49.6,0,117.7,0,200.5c0,47.8,21,90.6,54.3,119.8c0,0,41.9-21.4,41.9-21.4l0,0c-32.4-21-53.8-57.4-53.8-98.8c0-58.8,43.1-107.5,99.5-116.3c1.7-0.3,2.9,1.7,2,3.1L122.5,119.1z"/></g></svg>
                                    </a>
                                </div>
                                <div style="flex-grow: 1;flex-shrink: 1;">
                                    <input id="ms_givensolve" type="text" name="ms_givensolve" maxlength="<?php echo $templatedata['с_maxlength']; ?>" placeholder="Введите цифры*" required="required" />
                                    <span id="ms_givensolve-error" class="s9msgr-inputerror" style="display: none;">Вы должны ввести в это поле цифры, изображенные слева</span>
                                </div>
                            </div>
                            <br /><br />
                            <input id="ms_submit" type="button" value="Создать обращение" />
                            <input id="ms_session" type="hidden" name="ms_session" value="<?php echo $templatedata['ms_session']; ?>" />
                            <?php if($templatedata['prefilled']): ?>
                                <input id="ms_prefilled" type="hidden" name="ms_prefilled" value="<?php echo str_replace('"', "'", json_encode($templatedata['prefilled'])); ?>" />
                                <?php if(isset($templatedata['pc'])): ?>
                                    <input id="pc" type="hidden" name="pc" value="<?php echo $templatedata['pc']; ?>" />
                                <?php endif; ?>
                            <?php endif; ?>
                        </form>
                        <br /><br />
                        * - поля обязательные для заполнения
                        <br />
                        <h4>Статистика обращений:</h4>
                        <?php for($i = 0; $i < count($templatedata['stats']); $i++): ?>
                            <?php echo $templatedata['stats'][$i]['slug']; ?>: <?php echo $templatedata['stats'][$i]['value']; ?><br />
                        <?php endfor; ?>
                    <?php endif; ?>