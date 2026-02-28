<?
include $_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php';
function el_getParamArray($query, $key, $val){
	$arrOut=array();
	$false='Нет параметров';
	$ar=el_dbselect($query, 0, $ar);
	$arr=mysql_fetch_assoc($ar);
	if(mysql_num_rows($ar)>0){
		do{
			$arrOut[$arr[$key]]=$arr[$val];
		}while($arr=mysql_fetch_assoc($ar));
		return $arrOut;
	}else{
		return $false;
	}
}

function el_detParamArrayFiles($dirName){
	$arrOut=array();
	$dirName=$_SERVER['DOCUMENT_ROOT'].'/tmpl/'.$dirName;
	if(file_exists($dirName)) {
       $dir = dir($dirName);
       while($file = $dir->read()) {
           if($file != '.' && $file != '..') {
				include $dirName.'/'.$file;
				$arrOut[$file]=$template_name;
           }
       }
	   return $arrOut;
   } else {
       return false;
   }
}

$functionPropmenu=array(
	'el_pagemenu'=>       array(
								'fns_name'=>'Меню подразделов',
								'fns_description'=>'Выводит меню подразделов в текущем разделе в два столбца.'
								),
	'el_menut_simple'=>   array(
								'fns_name'=>'Простое меню',
								'fns_description'=>'Выводит простое меню разделов первого уровня в ненумерованном списке.',
								'class'=>'Имя CSS-класса для списка.'
							),
	'el_menutree'=>       array(
								'fns_name'=>'Древовидное меню',
								'fns_description'=>'Выводит древовидное меню разделов, находящихся в родительском разделе.',
								//'orient'=>'V',
								'displaymode'=>array(
													'block'=>'Показывать изначально раскрытые подпункты',
													'none'=>'Не показывать изначально подпункты'
													),
								'parent_path'=>array('control_desc'=>'Выберите родительский раздел. Меню выведет дочерние разделы. Для вывода родительских разделов выберите главный раздел сайта.',
								el_getParamArray("SELECT path, name FROM cat", 'path', 'name'))
						    ),
	'el_menut_expand'=>	  array(
								'fns_name'=>'Раскрывающееся меню',
								'fns_description'=>'Выводит "разъезжающееся" меню разделов, находящихся в родительском разделе.',
								'parent_path'=>array('control_desc'=>'Выберите родительский раздел. Меню выведет дочерние разделы. Для вывода родительских разделов выберите главный раздел сайта.',
								el_getParamArray("SELECT path, name FROM cat", 'path', 'name')), 
								'style'=>'Имя CSS-класса для списка.', 
								'display'=>array(
												'none'=>'Не показывать изначально подпункты',
												'block'=>'Показывать изначально раскрытые подпункты'
												),
							),	
	'el_menupart'=>       array(
								'fns_name'=>'Меню подраздела',
								'fns_description'=>'Выводит простое меню разделов в ненумеровнном списке, находящихся в родительском разделе.',
								'parent_path'=>array('control_desc'=>'Выберите родительский раздел. Меню выведет дочерние разделы. Для вывода родительских разделов выберите главный раздел сайта.',
								el_getParamArray("SELECT path, name FROM cat", 'path', 'name')), 
								'classname'=>'Имя CSS-класса для списка.'
								),
	'el_menupartft'=>     array(
								'fns_name'=>'Программируемое меню',
								'fns_description'=>'Выводит меню, настраиваемое шаблонами.',
								'parent_path'=>array('control_desc'=>'Выберите родительский раздел. Меню выведет дочерние разделы. Для вывода родительских разделов выберите главный раздел сайта.',
								el_getParamArray("SELECT path, name FROM cat", 'path', 'name')), 
								/*'template'=>'Имя файла шаблона для пунктов меню. Шаблон может использовать три переменные: <ul><li>$row_menupart - это ассоциативный массив с индексами "id", "path", "name". В них содержаться уникальный id раздела, относительный путь к разделу (используется в ссылке) и название раздела соответсвенно.</li><li>$script - содержит вызов функций на javascript для реакции пункта меню при наведении курсора.</li><li>$clas - содержит имя CSS-класса</li></ul>Сам файл шаблона распологается в папке "tmpl" в корне сайта.',*/
								'template'=>array(
												'control_desc'=>'Выберите заранее созданный шаблон пункта меню',
												el_detParamArrayFiles('menu')
												),
								'class'=>'Имя CSS-класса для пункта меню без наведения курсора', 
								'altclass'=>'Имя CSS-класса для пункта меню при наведении курсора',
								'viewMode'=> array(
												'div'=>'Меню на основе слоев',
												'table'=>'Меню на основе таблицы'
												)
								)					
);

$functionProptext=array(
	'el_pageprint'=>   array(
								'fns_name'=>'Вывод контента раздела',
								'fns_description'=>'Выводит заголовок или текст текущего раздела.',
								'var'=> array(
											'caption'=>'Заголовок раздела',
											'text'=>'Текст раздела',
											),
								),
	'el_infoblock'=> array(
								'fns_name'=>'Вывод контента из выбранного инфоблока',
								'fns_description'=>'Выводит HTML-текст из предопределенного инфоблока.',
								'id'=> array('control_desc'=>'Выберите инфоблок, из которого будет выводиться контент.', el_getParamArray("SELECT id, name FROM infoblocks", 'id', 'name'))
								)
);
$functionPropanons=array(
		'el_anonsNews'=>array(	'fns_name'=>'Анонсы новостей',
								'fns_description'=>'Выводит анонсы последних новостей.',
								'maxRows'=>'Количество показываемых новостей', 
								'template'=>array(
												'control_desc'=>'Выберите тип шаблона', 
												array(
													'simple'=>'Дата и заголовок новости',
													'full'=>'Дата, заголовок и краткий анонс новости'
													),												
												),
								'url'=>array(
												'control_desc'=>'Выберите новостной раздел, из которого выводятся последние новости для ссылки на детальное описание', 
												el_getParamArray("SELECT path, title FROM content WHERE kod='news'", 'path', 'title')
												),
								),
		'el_anonsArticles'=>array(	'fns_name'=>'Анонсы статей',
								'fns_description'=>'Выводит анонсы последних статей.',
								'maxRows'=>'Количество показываемых статей', 
								'template'=>array(
												'control_desc'=>'Выберите тип шаблона', 
												array(
													'simple'=>'Дата и заголовок статьи',
													'full'=>'Дата, заголовок и краткий анонс статьи'
													),												
												),
								'url'=>array(
												'control_desc'=>'Выберите раздел со статьями, из которого выводятся последние статьи для ссылки на детальное описание', 
												el_getParamArray("SELECT path, title FROM content WHERE kod='articles'", 'path', 'title')
												),
								),
		'el_anonsCatalog'=>array(	
								'fns_name'=>'Анонсы каталогов',
								'fns_description'=>'Выводит анонсы последних аписей из каталога.',
								'table -p'=>array(
												'control_desc'=>'Выберите каталог', 
												el_getParamArray("SELECT catalog_id, name FROM catalogs", 'catalog_id', 'name')
												),
								'url'=>array(
												'control_desc'=>'Выберите раздел, в котором подключен этот каталог для ссылки на детальное описание', 
												el_getParamArray("SELECT path, name FROM cat", 'path', 'name')
												),
								'maxRows'=>'Количество показываемых записей', 
								'template'=>array(
												'control_desc'=>'Выберите тип шаблона', 
												el_getParamArray("SELECT id, name FROM catalog_templates WHERE type='Дизайн строки'", 'id', 'name')											
												)
								)
);
$functionPropcalend=array(
		'el_calendNews'=>array(	'fns_name'=>'Каледнарь новостей',
								'fns_description'=>'Выводит календарь существующих дат в новостях',
								'url'=>array(
												'control_desc'=>'Выберите раздел, куда будет вести календарь при клике', 
												el_getParamArray("SELECT path, name FROM cat", 'path', 'name')
												),
								),
		'el_calendArticles'=>array(	'fns_name'=>'Каледнарь статей',
								'fns_description'=>'Выводит календарь существующих дат в статьях',
								'url'=>array(
												'control_desc'=>'Выберите раздел, куда будет вести календарь при клике', 
												el_getParamArray("SELECT path, name FROM cat", 'path', 'name')
												),
								),
		'el_calendCatalog'=>array(	'fns_name'=>'Каледнарь каталогов',
								'fns_description'=>'Выводит календарь существующих дат в каталогах',
								'table -p'=>array(
												'control_desc'=>'Выберите каталог', 
												el_getParamArray("SELECT catalog_id, name FROM catalogs", 'catalog_id', 'name')
												),
								'url'=>array(
												'control_desc'=>'Выберите раздел, куда будет вести календарь при клике', 
												el_getParamArray("SELECT path, name FROM cat", 'path', 'name')
												),
								'year_field'=>array(
													'control_desc'=>'Укажите поле с датой в каталоге для построения календаря',
													el_getParamArray("SELECT name, field FROM catalog_prop WHERE catalog_id='".$_GET['table']."'", 'field', 'name')
													),
								)
);

$functionProppolls=array(
		'el_poll'=>array(	'fns_name'=>'Опросы',
								'fns_description'=>'Выводит форму заранее созданного опроса посетителей',
								'id'=>array(
												'control_desc'=>'Выберите опрос', 
												el_getParamArray("SELECT poll_id, question   FROM poll_index", 'poll_id', 'question')
												),
								)
);
?>