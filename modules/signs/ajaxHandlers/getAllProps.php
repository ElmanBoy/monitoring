<?php

use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$props = $db->getRegistry('regprops', '', [], ['id', 'name', 'comment']);
foreach ($props['array'] as $item) {
    echo '
                                <li class="item"' . (strlen($item[2]) > 0 ? 'title="' . $item[2] . '"' : '') . '>
                                    <div class="el_data">
                                        <label for="prop' . $item[0] . '" style="margin-left: 40px;">' . $item[1] . '</label>
                                        <div class="custom_checkbox">
                                            <label class="container">
                                                <input type="checkbox" name="prop' . $item[0] . '" id="prop' . $item[0] . '" tabindex="-1" value="' . $item[0] . '">
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                    </div>
                                </li>';
}