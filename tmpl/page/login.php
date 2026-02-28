<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/header.php';
?>

<body>
<div class="wrap">
    <video autoplay loop muted playsinline class='bgvideo' id='bgvideo'>
        <source src='/images/video/background_13.mp4' type='video/mp4'></source>
    </video>
    <div id="bgs">
        <span id="bg_78">Фон 1</span>
        <span id="bg_13">Фон 2</span>
        <span id='bg_1'>Фон 3</span>
        <span id='bg_2'>Фон 4</span>
        <span id='bg_3'>Фон 5</span>
        <span id='bg_4'>Фон 6</span>
        <span id='bg_5'>Фон 7</span>
        <span id='bg_6'>Фон 8</span>
        <span id='bg_8'>Фон 9</span>
        <span id='bg_9'>Фон 10</span>
        <span id='bg_10'>Фон 11</span>
    </div>

    <div class="content enter_page">
        <h1>Подсистема контроля и надзора</h1>
        <div class="welcome" id="welocme_form">

            <form method="post" class="ajaxFrm" onsubmit="return false" id="login">
                <div class="item">
                    <div class="el_data">

                        <input class="el_input" type="text" name="user" placeholder="Логин">
                        <label>Логин</label>
                    </div>
                </div>
                <div class="item">
                    <div class="el_data">

                        <input class="el_input" type="password" name="password" autocomplete="no" placeholder="Пароль">
                        <label>Пароль</label>
                    </div>
                </div>
                <button class="button icon text">Войти по логину</button>
            </form>
            <div class="divider">
                <span>или</span>
            </div>
            <div class="confirm">
                <button class='button icon text' id="srtAuth">Войти по ЭЦП</button>
            </div>
        </div>
    </div>

</div>

<script src="/js/assets/call_popups.js"></script>
<script src='/js/assets/certificate.js'></script>

<script>
    /*setTimeout(function () {
        document.getElementById('welocme_form').style.opacity = '1';
    }, 1000);*/
    $(document).ready(function(){
        const video = document.getElementById('bgvideo');
        video.load();
    })
    $("#bgs span").on("click", function(e){
        $("#bgs span").removeClass("active");
        $(this).addClass("active");
        let number = $(this).attr("id").replace("bg_", "");
        $("#bgvideo").attr("src", "/images/video/background_" + number + ".mp4");
    });

    /*if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js')
            .then(function(registration) {
                console.log('Service Worker зарегистрирован:', registration);
            })
            .catch(function(error) {
                console.log('Ошибка регистрации Service Worker:', error);
            });
    }*/
</script>

<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/footer.php';
?>