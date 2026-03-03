<?php
use Core\Db;
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db;

$mods = $db->getRegistry('modules', ' order by id');
$modules = $mods['result'];

$role = $db->selectOne('roles', ' where id = ?', [intval($_POST['params'])]);
$permissions = json_decode($role->permissions, true);

//Открываем транзакцию
$busy = $db->transactionOpen('roles', intval($_POST['params']));
$trans_id = $busy['trans_id'];

if($busy != []){
?>
<div class="pop_up drag" style="width: 60rem">
    <div class="title handle">

        <div class="name">Редактирование роли</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm noreset" id="role_edit" onsubmit="return false">
            <div class="group">
                <input type="hidden" name="role_id" value="<?=$role->id?>">
                <input type="hidden" name="trans_id" value="<?=$trans_id?>">
                <div class="item  w_50 ">
                    <div class="el_data">
                        <label>Наименование</label>
                        <input required type="text" class="el_input" name="name" value="<?=$role->name?>">
                    </div>
                </div>
                <div class="item w_50">
                    <select required data-label="Статус" name="active">
                        <option value="1"<?=($role->active == 1) ? ' selected="selected"' : ''?>>
                            Активна
                        </option>
                        <option value="0"<?=($role->active == 0) ? ' selected="selected"' : ''?>>
                            Заблокирована
                        </option>
                    </select>
                </div>
            </div>
            <div class="group group_scroll">
            <?      
                        $c = 0;                   
                        reset($modules);
                        foreach ($modules as $module){
                            $perm = $permissions[$module->id];
                            $view = '';
                            $view_class = '';
                            if($perm['view']){
                                $view = ' checked';
                                $view_class = ' active';
                            }
                            $edit = '';
                            $edit_class = '';
                            if ($perm['edit']) {
                                $edit = ' checked';
                                $edit_class = ' active';
                            }
                            $delete = '';
                            $delete_class = '';
                            if ($perm['delete']) {
                                $delete = ' checked';
                                $delete_class = ' active';
                            }

                            echo '
                            <div class="item w_25">

                    <div class="el_data">
                        <div class="el_name">'.$module->name.'</div>
                        <input type="hidden" name="modules[]" value="'.$module->id.'">
                    </div>
                </div>
                            
                            <div class="item" >
                    <div class="custom_checkbox" title="Просмотр">
                        <label class="container'.$view_class.'"><span class="material-icons">
                                        visibility
                                    </span><input type="checkbox" name="view'.$c.'" value="y"'.$view.'><span class="checkmark"></span></label>
                    </div>
                    <div class="custom_checkbox" title="Изменение">
                        <label class="container'.$edit_class.'"><span class="material-icons">
                                        edit
                                    </span><input type="checkbox" name="edit'.$c.'" value="y"'.$edit.'><span class="checkmark"></span></label>
                    </div>
                    <div class="custom_checkbox" title="Удаление">
                        <label class="container'.$delete_class.'"><span class="material-icons">
                                        delete
                                    </span><input type="checkbox" name="delete'.$c.'" value="y"'.$delete.'><span class="checkmark"></span></label>
                    </div>
                </div>
                            ';
                            $c++;
                        } 
                        ?>
                    </div>

            <div class="group">
                <div class="item w_100">
                    <div class="el_data">
                        <label>Примечания</label>
                        <textarea class="el_textarea"><?=$role->comment?></textarea>
                    </div>
                </div>
            </div>
            <div class="confirm">
                <!-- <div class="autor">
                    <div class="date_create"><span>Редактирование:</span>01.05.2021</div>
                    <div class="user"><span>Пользователь:</span><a href="#">Помидоркин С.П.</a></div>
                </div> -->

                <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>
                <?/*button class="button icon text"><span class="material-icons">control_point_duplicate</span>Клонировать
                </button>
                <button class="button icon text"><span class="material-icons">delete_forever</span>Удалить</button*/?>
            </div>
        </form>
    </div>
    <script>
        el_roles.create_init();
        $("#role_edit .close").on("click", function(){
            $.post("/", {ajax: 1, action: "transaction_close", id: <?=$trans_id?>}, function(){})
        });
        $('.preloader').fadeOut('fast');
    </script>
    <?php
}else{
?>
  <script>
  alert("Эта запись редактируется пользователем <?=$busy['user_name']?>");
  el_app.dialog_close("role_edit");
  </script>
    <?
}
?>
