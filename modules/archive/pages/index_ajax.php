<?php

use Core\Gui;
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$regId = 66; // ID реестра документов — как в модуле documents

$gui  = new Gui;
$db   = new Db;
$auth = new Auth();

$gui->set('module_id', 25);

$table        = $db->selectOne('registry', ' where id = ?', [$regId]);
$documentacial = $db->getRegistry('documentdocuments');
$items         = $db->getRegistry($table->table_name);

$regs = $gui->getTableData($table->table_name, 'AND active = -1');
?>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
            'title'   => 'Архив документов',
            'renew'   => 'Сбросить все фильтры',
            'restore' => 'Восстановить из архива',
        ]);
        ?>
    </div>
</div>
<div class="scroll_wrap">
    <form method="post" id="registry_items_delete" class="ajaxFrm">
        <input type="hidden" name="registry_id" id="registry_id" value="<?= $regId ?>">
    </form>
    <form method="post" id="registry_items_restore" class="ajaxFrm">
        <input type="hidden" name="registry_id" value="<?= $regId ?>">
    </form>
    <form method="post" id="registry_items_delete_real" class="ajaxFrm">
        <input type="hidden" name="registry_id" value="<?= $regId ?>">
        <table class="table_data" id="tbl_registry_items">
            <thead>
            <tr class="fixed_thead">
                <th>
                    <div class="custom_checkbox">
                        <label class="container" title="Выделить все">
                            <input type="checkbox" id="check_all"><span class="checkmark"></span>
                        </label>
                    </div>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'documents',
                        '№',
                        'id',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'documents',
                        'Наименование',
                        'name',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'documents',
                        'Тип документа',
                        'documentacial',
                        'constant',
                        $documentacial['array']
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'documents',
                        'Перемещён в архив',
                        'archived_at',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'documents',
                        'Кем архивирован',
                        'archived_by',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th>
                    <div class="head_sort_filter">Примечания</div>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($regs as $reg) {
                // Имя пользователя, выполнившего архивирование
                $archivedByName = '';
                if (intval($reg->archived_by) > 0) {
                    $archivedUser = $db->selectOne('users', ' WHERE id = ?', [$reg->archived_by]);
                    $archivedByName = trim($archivedUser->surname . ' ' . $archivedUser->name . ' ' . $archivedUser->middle_name);
                }

                echo '<tr data-id="' . $reg->id . '" data-parent="' . $regId . '" tabindex="0" class="noclick">
                    <td>
                        <div class="custom_checkbox">
                            <label class="container">
                                <input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $reg->id . '">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                    </td>
                    <td>' . $reg->id . '</td>
                    <td class="group">' . stripslashes($reg->name) . '</td>
                    <td>' . ($documentacial['array'][$reg->documentacial] ?? '—') . '</td>
                    <td>' . ($reg->archived_at ? date('d.m.Y H:i', strtotime($reg->archived_at)) : '—') . '</td>
                    <td>' . ($archivedByName ?: '—') . '</td>
                    <td>' . $reg->comment . '</td>
                </tr>';
            }
            ?>
            </tbody>
        </table>
    </form>
    <?
    echo $gui->paging();
    ?>
</div>
<script src="/modules/archive/js/archive.js?v=<?= $gui->genpass() ?>"></script>
