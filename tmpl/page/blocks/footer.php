<div class="preloader"></div>

<div class='pop_up drag ui-draggable' id='notificationsList'>

    <div class='pop_up_body' style='min-height: 27rem;'>
        <div id="notificationHeader">
            <div class='material-icons close' title='Скрыть панель'>chevron_right</div>
            <h4>Уведомления</h4>
            <?
            if($_SESSION['user_settings'][0]['notificationSound'] == 'up' || !isset($_SESSION['user_settings']['notificationSound'])) {
                $soundIcon = 'volume_up';
                $soundTitle = 'Звук уведомлений включен';
            }else {
                $soundIcon = 'volume_off';
                $soundTitle = 'Звук уведомлений выключен';
            }
            ?>
            <span class="material-icons sound" id="notificationSound" title="<?=$soundTitle?>"><?=$soundIcon?></span>
            <span class="material-icons clear" id="notificationDelete" title="Удалить все уведомления">delete</span>
        </div>
        <div class='group'>
            <?
            $nots = $notes->getRecordsToPanel($_SESSION['user_id']);
            echo $nots['messages'];
            ?>
        </div>
    </div>
</div>

<div class="pop_up drag ui-draggable" id="filterer">

    <div class="pop_up_body" style="min-height: 27rem;">
        <form class="ajaxFrm noreset" id="filterer_frm" onsubmit="return false">
            <div class="material-icons close" title="Скрыть панель">chevron_right</div>
            <h4>Фильтр</h4>
            <div class="group">
                <input type="hidden" name="module" value="3">
                ohs_registry
            </div>

            <div class="confirm">

                <?/*button class="button icon text" type="submit">
                    <span class="material-icons">done</span>Применить
                </button*/?>
                <button class="button icon text" type="reset" from="oper_view_settings">
                    <span class="material-icons">restart_alt</span>Сбросить
                </button>

            </div>
        </form>
    </div>
</div>
<?/*button id="install-button" style="display: none">Установить приложение</button*/?>

<?php /*
if (($auth->haveUserRole(3) || $auth->haveUserRole(1))) {
    ?>
    <script>

        $(document).ready(function () {
            let installPromptEvent;

            $(window).on('beforeinstallprompt', (event) => {
                // Предотвращаем автоматический показ prompt
                event.preventDefault();
                // Сохраняем событие для использования позже
                installPromptEvent = event.originalEvent;
                // Показываем кнопку установки
                $('#install-button').show();
                console.log('PWA может быть установлено', event);
            });

            $('#install-button').on('click', async () => {
                if (!installPromptEvent) {
                    console.log('Событие установки недоступно');
                    return;
                }

                try {
                    // 1. Показываем стандартное диалоговое окно установки
                    installPromptEvent.prompt();

                    // 2. Ждем выбора пользователя
                    const choiceResult = await installPromptEvent.userChoice;

                    if (choiceResult.outcome === 'accepted') {
                        console.log('Пользователь согласился на установку');
                    } else {
                        console.log('Пользователь отказался от установки');
                    }
                } catch (err) {
                    console.error('Ошибка при запросе установки:', err);

                    // Альтернативный способ установки (если prompt() не работает)
                    if (window.matchMedia('(display-mode: standalone)').matches) {
                        console.log('Приложение уже установлено');
                    } else {
                        console.log('Попробуйте ручную установку через меню браузера');
                    }
                } finally {
                    installPromptEvent = null;
                    $('#install-button').hide();
                }
            });

            // Проверяем, установлено ли приложение
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            if (isStandalone) {
                console.log('Приложение запущено в standalone-режиме');
                $('#install-button').hide();
            } else {
                console.log('Приложение запущено в браузере');
            }

            // Регистрация Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(registration => console.log('SW registered:', registration))
                    .catch(err => console.error('SW registration failed:', err));
            }
        });
    </script>
    <?php
}*/
if(isset($_GET['open_dialog']) && strlen($_GET['open_dialog']) > 0){
    $open_dialog = json_decode($_GET['open_dialog']);
    $module = 'documents';
    $mode = 'agreement';
    if(isset($_GET['module'])){
        $module = stripslashes(strip_tags($_GET['module']));
    }
    if(isset($_GET['mode'])){
        $mode = stripslashes(strip_tags($_GET['mode']));
    }

    echo '<script>
    el_app.setMainContent("/'.$module.'");
    el_app.dialog_open("'.$mode.'", '.json_encode($open_dialog).', "'.$module.'");
    </script>';
}
?>
</body>

</html>
