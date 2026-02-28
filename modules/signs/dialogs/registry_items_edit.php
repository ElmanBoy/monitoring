<?php
use \Core\Db;
use Core\Gui;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$db = new Db;
$gui = new Gui();
$reg = new Registry();
$reg_id = intval($_POST['params'][1]);
$row_id = intval($_POST['params'][0]);

$table = $db->selectOne('signs', ' where id = ?', [$row_id]);

class JsonFormatter {
    public static function format($data, $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) {
        return json_encode($data, $options);
    }

    public static function outputToTextarea($data, $textareaId = 'jsonOutput', $readonly = true): string
    {
        $json = self::format($data);
        $readonlyAttr = $readonly ? 'readonly' : '';

        return <<<HTML
<textarea id="$textareaId" rows="30" cols="80" 
          style="font-family: monospace; white-space: pre;" $readonlyAttr>
<?php echo htmlspecialchars($json); ?>
</textarea>
HTML;
    }
}


?>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css'>
<script src='https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js'></script>
<div class="pop_up drag">
    <div class="title handle">

        <div class="name">Просмотр подписи</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <div class="group">
        <div class="item w_100">
            <div class="el_data">
            <textarea class="el_textarea" id="jsonInput" style='font-family: monospace; display: none' rows="30">
                <?
                print_r( $table->sign );
                ?>
            </textarea>
                <div id='jsonOutput'
                     style='border: 1px solid #ccc; padding: 10px; font-family: monospace; white-space: pre; width: 100%;overflow: auto;'></div>
            <?
            //echo JsonFormatter::outputToTextarea($table->sign);
            ?>
            </div>
        </div>
        </div>
            <div class="confirm">
                <button class='button icon close'><span class='material-icons'>close</span>Закрыть</button>
            </div>

    </div>

</div>
    <script>
        el_app.mainInit();
        el_registry.create_item_init();
        $(document).ready(function() {
            try {
                const inputJson = $('#jsonInput').val();
                const parsedJson = JSON.parse(inputJson);
                const formattedJson = JSON.stringify(parsedJson, null, 4);

                // Выводим с подсветкой синтаксиса
                $('#jsonOutput')
                    .html('<pre><code class="language-json">' +
                        hljs.highlight(formattedJson, {language: 'json'}).value +
                        '</code></pre>');
            } catch (error) {
                $('#jsonOutput').html('<pre style="color: red;">Ошибка: ' + error.message + '</pre>');
            }
        });
    </script>