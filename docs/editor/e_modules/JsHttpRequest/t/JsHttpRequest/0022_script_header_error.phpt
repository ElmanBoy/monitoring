<div id="TEST">
    <?=basename($_SERVER['REQUEST_URI'])?>: setRequestHeader() method (errorous)
</div>


<?include "contrib/init.php"?>
<div id="FILE">
    <script>
    doQuery('script', 'get', 123, null, null, null, null, function(req) { req.setRequestHeader('a', 'b') });
    </script>
</div>


<pre id="EXPECT">
JsHttpRequest: Method setRequestHeader() cannot work together with the SCRIPT loader.
</pre>