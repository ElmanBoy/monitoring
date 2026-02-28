<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$modules = R::findAll('ohs_modules', ' active = 1');
?>
<script>
    var modules = [
        <?
        reset($modules);
        foreach ($modules as $module){
            echo '{value: "'.$module->id.'", text: "'.$module->name.'"},'."\n";
        }
        ?>
    ];
</script>
<div class="pop_up drag" style='width: 60vw;'>
    <div class="title handle">

        <div class="name">Новая роль</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm" id="role_create" onsubmit="return false">
            <div class="group">

                <div class="item  w_50 ">
                    <div class="el_data">
                        <label>Наименование</label>
                        <input required type="text" class="el_input" name="name" value="">
                    </div>
                </div>

            </div>
            <div class="group group_scroll">
                
                <!--
                    <select required data-label="Модуль" data-place="Выберите" name="modules[]" class="select_modules">
                        <option value="">&nbsp;</option>-->
                        <?      
                        $c = 0;                   
                        reset($modules);
                        foreach ($modules as $module){
                            echo '
                            <div class="item w_25">

                    <div class="el_data">
                        <div class="el_name">'.$module->name.'</div>
                        <input type="hidden" name="modules[]" value="'.$module->id.'">
                    </div>
                </div>
                            
                            <div class="item" >
                    <div class="custom_checkbox" title="Просмотр">
                        <label class="container"><span class="material-icons">
                                        visibility
                                    </span><input type="checkbox" name="view'.$c.'" value="y"><span class="checkmark"></span></label>
                    </div>
                    <div class="custom_checkbox" title="Изменение">
                        <label class="container"><span class="material-icons">
                                        edit
                                    </span><input type="checkbox" name="edit'.$c.'" value="y"><span class="checkmark"></span></label>
                    </div>
                    <div class="custom_checkbox" title="Удаление">
                        <label class="container"><span class="material-icons">
                                        delete
                                    </span><input type="checkbox" name="delete'.$c.'" value="y"><span class="checkmark"></span></label>
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
                        <textarea class="el_textarea" name="comment"></textarea>
                    </div>
                </div>
            </div>








            <div class="confirm">
                <!-- <div class="autor">
                    <div class="date_create"><span>Редактирование:</span>01.05.2021</div>
                    <div class="user"><span>Пользователь:</span><a href="#">Помидоркин С.П.</a></div>
                </div> -->

                <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>
                <?/*button class="button icon text"><span class="material-icons">control_point_duplicate</span>Клонировать</button*/?>

            </div>
        </form>
    </div>

</div>
<script>
    el_roles.create_init();
</script>