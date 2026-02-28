<?
//header("Content-type: text/html; charset=utf-8");
//header("Cache-Control: no-store, no-cache, must-revalidate");
//header("Cache-Control: post-check=0, pre-check=0", false);
include $_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php';
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Поиск запчастей</title>
<style>
body{
	font-family:"Gill Sans", "Gill Sans MT", "Myriad Pro", "DejaVu Sans Condensed", Helvetica, Arial, sans-serif;
	font-size:13px;
	padding: 15px;
}
table, table td{
	border-collapse:collapse;
	vertical-align:top;
	border-color: #9FC7E8;
	white-space:nowrap;
}
table tr:hover td{
	background-color:rgb(247, 247, 247);
}
.stock td{
	border:1px dotted #CCCCCC;
}
</style>
<?
$brands = array(
"555",
"ABS",
"ACDelco",
"AD",
"Adriauto",
"ADVICS",
"Airtex",
"Aisin",
"Ajusa",
"Akitaka",
"Akyoto",
"Al-ko",
"Alco Filter",
"Alkar",
"Allied Nippon",
"AMD",
"Api",
"ASAM",
"Asva",
"ATE",
"ATL",
"AVANTECH",
"B-TECH",
"Behr",
"Bilstein",
"BJS",
"Blue Print",
"BMW",
"Boge",
"Bosch",
"Brembo",
"Bremi",
"Bugatti",
"CARMATE",
"CASP",
"Champion",
"Chery",
"Chrysler",
"Cifam",
"Citroen/Peugeot",
"COMTECH",
"Concord",
"Contitech",
"Corteco",
"CTR",
"Dayco",
"Delphi",
"Denso",
"Depo",
"Dolz",
"EAA-Joints",
"Eagleye",
"Elring",
"Elwis Royal",
"ERA",
"ERT",
"Esso",
"Facet",
"FAE",
"FAG",
"Febest",
"Febi",
"Fenox",
"Ferodo",
"Filtron",
"Ford",
"Fram",
"Freccia",
"Frenkit",
"Frixa",
"Gates",
"Geely",
"General Ricambi",
"Girling",
"GKN-Lobro",
"GKN-Spidan",
"Glyco",
"GM",
"GMB",
"Goetze",
"GSP",
"Han",
"HDK",
"Hella",
"Hengst",
"Hepu",
"HK",
"HKT",
"Honda",
"Huco",
"Hyundai/Kia",
"INA",
"Japan Parts",
"jFBK",
"JP Group",
"JS Asakashi",
"K+F",
"Kamoka",
"KAVO PARTS",
"Kilen",
"Klakson",
"Knecht",
"KOITO",
"Koyo",
"KP",
"KYB",
"KYOSAN",
"Lemforder",
"Lesjofors",
"Liqui Moly",
"LPR",
"LUK",
"Luzar",
"LYNXauto",
"Mahle",
"Mando",
"Mann",
"Mapco",
"Maruichi",
"Mazda",
"MecArm",
"Mercedes",
"Meyle",
"Mintex",
"Mitsubishi",
"Monroe",
"Moog",
"Mopisan",
"MR Universal",
"MRK",
"NACHI",
"Narva",
"NBN",
"NEOLUX",
"NGK",
"NGN",
"Nipparts",
"Nissan",
"Nissens",
"Nisshinbo",
"NK",
"NKN",
"NOK",
"NPW",
"NSK",
"NT",
"NTN",
"OBK",
"Opel",
"Optimal",
"Osram",
"PATRON",
"Philips",
"Pierburg",
"PMC",
"Polcar",
"Pomax",
"Porsche",
"Qsten",
"Rancho",
"RBI",
"Renault",
"Rixenberg",
"RTS",
"Ruville",
"Sachs",
"Samko",
"Sangsin",
"Sasic",
"Seiken",
"Seinsa",
"SEIWA",
"Shell",
"Sidem",
"SKF",
"SNR",
"Ssangyong",
"Starke",
"Stellox",
"Subaru",
"Suzuki",
"Swag",
"SWF",
"Taiho",
"Tama",
"TCL",
"TECH-AS",
"Tesla",
"Textar",
"THO",
"TOKAI",
"Toyota",
"Trico",
"TRW",
"TSN",
"TYC",
"TYG",
"Ufi",
"Union",
"URW",
"VAG",
"Vaico",
"Valeo",
"Vernet",
"VIC",
"Victor Reinz",
"Volvo",
"WTW",
"Wynn's",
"ZF Lenksysteme",
"ZMZ",
"Автоброня",
"Автоупор",
"АСОМИ",
"ЗИЛ"
)
?>
</head>

<body>
<form method="POST">
Номер детали или название:<br>
<input name="dn" type="text" placeholder="Номер детали" value="<?=$_POST['dn']?>"><br>

Производитель:<br>
<select name="brand">
	<option value=""></option>
<?
for($b = 0; $b < count($brands); $b++){
	$sel = ($_POST['brand'] == $brands[$b]) ? ' selected' : '';
	echo '<option value="'.$brands[$b].'"'.$sel.'>'.$brands[$b].'</option>'."\n";
}
?>
</select><br>

<label for="ex"><input id="ex" type="checkbox" name="presence" value="Y" <?=isset($_POST['presence']) ? 'checked' : ''?>> только в наличии</label><br>

<label for="an"><input id="an" type="checkbox" name="analogs" value="Y" <?=isset($_POST['analogs']) ? 'checked' : ''?>> с аналогами</label><br>

<label for="sort"><input id="sort" type="checkbox" name="sort" value="Y" <?=isset($_POST['sort']) ? 'checked' : ''?>> с сортировкой по цене</label><br>

<input type="submit" value="Искать">
</form>
<p>&nbsp;</p>
<?php
//print_r($_POST);
//error_reporting(E_ALL);
$count = 0;

function cmp($a, $b) {
    if(is_array($a['price'])){
		$a['price'] = $a['price'][0];
	}
	 if(is_array($b['price'])){
		$b['price'] = $b['price'][0];
	}
	if ($a['price'] == $b['price']) {
		return 0;
	}
	return ($a['price'] < $b['price']) ? -1 : 1;
	
}

function mb_ucfirst($str) {
    $fc = mb_strtoupper(mb_substr($str, 0, 1));
    return $fc.mb_substr($str, 1);
}

function getJsonErrors(){
	 switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - Ошибок нет';
        break;
        case JSON_ERROR_DEPTH:
            echo ' - Достигнута максимальная глубина стека';
        break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Некорректные разряды или не совпадение режимов';
        break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Некорректный управляющий символ';
        break;
        case JSON_ERROR_SYNTAX:
            echo ' - Синтаксическая ошибка, не корректный JSON';
        break;
        case JSON_ERROR_UTF8:
            echo ' - Некорректные символы UTF-8, возможно неверная кодировка';
        break;
        default:
            echo ' - Неизвестная ошибка';
        break;
    }

    return PHP_EOL;	
}

//Получаем данные из japarts
function get_japarts(){
	global $count;
	$curl = curl_init();
	$brand = strlen($_POST['brand']) > 0 ? '&makename='.$_POST['brand'] : '';
	
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://www.japarts.ru/?id=ws&action=search&login=mmcplu&pass=dthcfhec$brand&detailnum=".$_POST['dn']."&cross=".(isset($_POST['analogs']) ? '1' : '0'),
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
		"cache-control: no-cache",
		"postman-token: fc3c0ef3-23cf-010f-984c-38f2cde252c6"
	  ),
	));
	
	$response = curl_exec($curl);
	$err = curl_error($curl);
	
	curl_close($curl);
	
	$resultArray = array();
	
	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
		$json = json_decode($response, true);
		
		for($i = 0; $i < count($json); $i++){
		  $exist = false;
		  if(isset($_POST['presence']) && intval($json[$i]['quantity']) > 0){
			  $exist = true;
		  }elseif(!isset($_POST['presence'])){
			  $exist = true;
		  }
		  
		  if($exist == true && $json[$i]['priceid']){
			 $resultArray[$count]['guid'] = $json[$i]['priceid'];
			 $resultArray[$count]['brand'] = $json[$i]['makename'];
			 $resultArray[$count]['pnumber'] = $json[$i]['detailnum'];
			 $resultArray[$count]['name'] = $json[$i]['detailname'];
			 $resultArray[$count]['price'] = $json[$i]['pricerur'];
			 $resultArray[$count]['count'] = $json[$i]['quantity'];
			 $resultArray[$count]['delivery'] = $json[$i]['time'];//.'/'.$json[$i]['timegar'];
			 $resultArray[$count]['src'] = $json[$i]['supcode'];
			 $resultArray[$count]['vendor'] = 'Japarts';
			  $resultArray[$count]['currency'] = 'руб.';
			  $resultArray[$count]['unit'] = 'шт';
			 $count++;
		  }
		}
		
		//echo getJsonErrors();
	}
	if(isset($_GET['japarts'])){echo '<pre>'; print_r($resultArray); echo '</pre>';}
	return $resultArray;
}

//Получаем данные из Rossko
function get_rossko_stocks($res){
	$resultArray = array();
	
	if($res->StocksList){
		$stock = $res->StocksList->Stock;
		//Если указаны цены на нескольких складах
		if(is_array($stock)){
			for($c = 0; $c < count($stock); $c++){
				$stockId[$c] = $stock[$c]->StockID;
				$stockPrice[$c] = $stock[$c]->Price;
				$stockCount[$c] = $stock[$c]->Count;
				$delivery[$c] = $stock[$c]->DeliveryTime;
			}
		//Иначе указывается одна цена
		}else{
			$stockId = '';
			$stockPrice = $stock->Price;
			$stockCount = $stock->Count;
			$delivery = $stock->DeliveryTime;
		}
		
		$resultArray['stockId'] = $stockId;
		$resultArray['price'] = $stockPrice;
		$resultArray['count'] = $stockCount;
		$resultArray['delivery'] = $delivery;
		
		return $resultArray;
	}else{
		return array();
	}
}

function get_rossko(){
	global $count;
	$resultArray = array();
	$client = new SoapClient("http://msk.rossko.ru/service/v1/GetSearch?wsdl");
	$result = $client->GetSearch(array(
	"KEY1"=>"0dde161cdd78fa133104554726a88381",
	"KEY2"=>"a79aabd3f33f5374af6a9c2c58178209",
	"TEXT"=>$_POST['dn']));
	
	$res = $result->SearchResults->SearchResult->PartsList->Part;
	//Выводим оригиналы
	if(is_array($res)){
		//Если деталь не на одном складе
		for($a = $count; $a < count($res) + $count; $a++){
			$exist = false; 
			$resIndex = $a - $count;
			
			if(count($res[$resIndex]->StocksList) > 0 && isset($_POST['presence'])){
				$exist = true; 
			}elseif(!isset($_POST['presence'])){
				$exist = true;
			}
			
			if($exist){
				$resultArray[$a]['guid'] =  $res[$resIndex]->GUID;
				$resultArray[$a]['brand'] = $res[$resIndex]->Brand;
				$resultArray[$a]['pnumber'] = $res[$resIndex]->PartNumber;
				$resultArray[$a]['name'] = $res[$resIndex]->Name;
				$resultArray[$a] += get_rossko_stocks($res[$resIndex]);
				$resultArray[$a]['src'] = '';
				$resultArray[$a]['vendor'] = 'Rossko';
				$resultArray[$a]['currency'] = 'руб.';
				$resultArray[$a]['unit'] = 'шт';
			}
		}
	}else{
		$exist = false;
		
		if(count($res->StocksList) > 0 && isset($_POST['presence'])){
			$exist = true;
		}elseif(!isset($_POST['presence'])){
			$exist = true;
		}
		
		if($exist && $res->GUID){
			$a = $count + 1;
			$resultArray[$a]['guid'] = $res->GUID;
			$resultArray[$a]['brand'] = $res->Brand;
			$resultArray[$a]['pnumber'] = $res->PartNumber;
			$resultArray[$a]['name'] = $res->Name;
			$resultArray[$a] += get_rossko_stocks($res);
			$resultArray[$a]['src'] = '';
			$resultArray[$a]['vendor'] = 'Rossko';
			$resultArray[$a]['currency'] = 'руб.';
			$resultArray[$a]['unit'] = 'шт';
		}
	}
	//Выводим кроссы
	if(isset($_POST['analogs']) && $res->CrossesList){
		$crosses = $res->CrossesList->Part; 
		if(is_array($crosses)){
			for($d = 0; $d < count($crosses); $d++){
				$exist = false;
				
				if(count($crosses[$d]->StocksList) > 0 && isset($_POST['presence'])){
					$exist = true;
				}elseif(!isset($_POST['presence'])){
					$exist = true;
				}
				
				if($exist){
					$a++;
					$resultArray[$a]['guid'] = 'Cross '.$crosses[$d]->GUID;
					$resultArray[$a]['brand'] = $crosses[$d]->Brand;
					$resultArray[$a]['pnumber'] = $crosses[$d]->PartNumber;
					$resultArray[$a]['name'] = $crosses[$d]->Name;
					$resultArray[$a] += get_rossko_stocks($crosses[$d]);
					$resultArray[$a]['src'] = '';
					$resultArray[$a]['vendor'] = 'Rossko';
					$resultArray[$a]['currency'] = 'руб.';
					$resultArray[$a]['unit'] = 'шт';
				}
			}
		}
	}
	
	if(isset($_GET['rossko'])){echo '<pre>'; print_r($result); echo '</pre>';}
	return $resultArray;
}

//Получаем данные из Autotrade
function get_autotrade_stocks($res){
	$resultArray = array();

	if($res->StocksList){
		$stock = $res->StocksList->Stock;
		//Если указаны цены на нескольких складах
		if(is_array($stock)){
			for($c = 0; $c < count($stock); $c++){
				$stockId[$c] = $stock[$c]->StockID;
				$stockPrice[$c] = $stock[$c]->Price;
				$stockCount[$c] = $stock[$c]->Count;
				$delivery[$c] = $stock[$c]->DeliveryTime;
			}
			//Иначе указывается одна цена
		}else{
			$stockId = '';
			$stockPrice = $stock->Price;
			$stockCount = $stock->Count;
			$delivery = $stock->DeliveryTime;
		}

		$resultArray['stockId'] = $stockId;
		$resultArray['price'] = $stockPrice;
		$resultArray['count'] = $stockCount;
		$resultArray['delivery'] = $delivery;

		return $resultArray;
	}else{
		return array();
	}

	for ($i = 0; $i < count($json['items']); $i++) {
		$exist = false;
		if (isset($_POST['presence']) && $json[$i]['price'] != '') {
			$exist = true;
		} elseif (!isset($_POST['presence'])) {
			$exist = true;
		}

		if ($exist) {
			$resultArray[$count]['guid'] = $json[$i]['id'];
			$resultArray[$count]['brand'] = $json[$i]['brand_name'];
			$resultArray[$count]['pnumber'] = $json[$i]['article'];
			$resultArray[$count]['name'] = $json[$i]['name'];
			$resultArray[$count]['price'] = $json[$i]['price'];

			for($a = 0; $a < count($json[$i]['stocks']); $a++){
				$stock = $json[$i]['stocks'][$a];
				if(substr_count(strtolower($stock['name']), 'москва') > 0 &&
					$stock['quantity_unpacked'] == '+' || $stock['quantity_packed'] == '+') {
					$resultArray[$count]['stockId'] = $stock['name'];
					$resultArray[$count]['count'] = '+';
					$resultArray[$count]['delivery'] = $json[$i]['delivery_period'];
				}
			}

			$resultArray[$count]['src'] = '';
			$resultArray[$count]['vendor'] = 'Autotrade';
			$count++;
		}
	}
}

function get_autotrade(){
	global $count;
	$resultArray = array();
	$url = 'https://api2.autotrade.su/?json';
	$hash = md5('mmc-plus@mail.ru'.md5('dthcfhec').'1>6)/MI~{J');
	$analogs = (isset($_POST['analogs'])) ? 1 : 0;

	$data = array(
		"auth_key" => $hash,
		"method" => "getItemsByQuery",
		"params" => array(
			"q" => array($_POST['dn']),
			"strict" => 0,
			"replace" => 1,//$analogs,
			"cross" => 1,//$analogs,
			"with_stocks_and_prices" => 1,
			"with_delivery" => 1
		)
	);

	$request = 'data=' . json_encode($data);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$html = curl_exec($ch);
	curl_close($ch);
	$json = json_decode($html, true);
	if(isset($_GET['autotrade'])){echo '<pre>'; print_r($json); echo '</pre>';}

	if($json['total'] > 0) {
		for ($i = 0; $i < count($json['items']); $i++) {
			$jsonItem = $json['items'][$i];
			$exist = false;
			if (isset($_POST['presence']) && $jsonItem['price'] != '') {
				$exist = true;
			} elseif (!isset($_POST['presence'])) {
				$exist = true;
			}

			if ($exist == true) {
				$resultArray[$count]['guid'] = $jsonItem['id'];
				$resultArray[$count]['brand'] = $jsonItem['brand_name'];
				$resultArray[$count]['pnumber'] = $jsonItem['article'];
				$resultArray[$count]['name'] = $jsonItem['name'];
				$resultArray[$count]['price'] = $jsonItem['price'];
				$resultArray[$count]['currency'] = $jsonItem['currency'];
				$resultArray[$count]['unit'] = $jsonItem['unit'];
				$co = 0;

				while(list($key, $val) = each($jsonItem['stocks'])){
					$stock = $jsonItem['stocks'][$key];

					if((substr_count(mb_strtolower($stock['name']), 'москва') > 0 ||
							substr_count(mb_strtolower($stock['name']), 'рязань') > 0) &&
						($stock['quantity_unpacked'] == '+' || $stock['quantity_packed'] == '+')) {

						$resultArray[$count]['stockId'][$co] = $stock['name'];
						$resultArray[$count]['count'][$co] = '+';
						$resultArray[$count]['delivery'][$co] = $stock['delivery_period'];
						$co++;
					}
				}

				$resultArray[$count]['src'] = '';
				$resultArray[$count]['vendor'] = 'Autotrade';
				$count++;
			}
		}
	}
	//echo getJsonErrors();

	return $resultArray;
}

if(isset($_POST['dn'])){
	$resultArray = array();
	$resultArray = get_japarts() + get_rossko() + get_autotrade();
	if(isset($_GET['result'])){echo '<pre>'; print_r($resultArray); echo '</pre>';}
}


if(count($resultArray) > 0){
	?>
	<table border="1" cellpadding="5" cellspacing="0">
	<tr>
		<th>#</th>
		<th>ID детали</th>
		<th>Производитель</th>
		<th>Номер детали</th>
		<th>Название</th>
		<th>Цена</th>
		<th>Кол-во</th>
		<th>Доставка</th>
		<th>Поставщик</th>
		<th>Источник</th>
	</tr>
	<?

	if(isset($_POST['sort'])){
		uasort($resultArray, 'cmp');
	}

	while(list($key, $val)= each($resultArray)){
		$exist = false;
		if (isset($_POST['presence']) && $val['price'] != '') {
			$exist = true;
		} elseif (!isset($_POST['presence'])) {
			$exist = true;
		}
		if($exist == true && $val['guid']){
			$price = number_format(intval($val['price']) * 1.05, 2, ',', ' ');
			echo '<tr>
						<td>'.$key.'</td>
						<td>'.$val['guid'].'</td>
						<td>'.$val['brand'].'</td>
						<td>'.$val['pnumber'].'</td>
						<td>'.mb_ucfirst($val['name']).'</td>';
						if(!is_array($val['stockId'])){
							echo '<td>'.$price.' '.$val['currency'].'</td>
							<td>'.$val['count'].' '.$val['unit'].'</td>
							<td>'.$val['delivery'].' д'.el_postfix($val['delivery'], 'ень', 'ня', 'ней').'</td>';
						}else{
							echo '<td colspan="3">
							<table cellpadding="5" cellspacing="0" class="stock" width="100%">';
							for($i = 0; $i < count($val['stockId']); $i++){
								$price1 = (strlen($val['price'][$i]) > 0) ? number_format(intval($val['price'][$i]) * 1.05, 2, ',', ' ').' '.$val['currency']
								: $price.' '.$val['currency'];
								echo '<tr>
										<td>склад '.$val['stockId'][$i].'</td>
										<td>'.$price1.'</td>
										<td>'.$val['count'][$i].' '.$val['unit'].'</td>
										<td>'.$val['delivery'][$i].' д'.el_postfix($val['delivery'][$i], 'ень', 'ня', 'ней').'</td>
									 </tr>';
							}
							echo '</table></td>';
						}
						echo '<td>'.$val['src'].'</td>
						<td>'.$val['vendor'].'</td>
					</tr>'."\n";
		}
	}
	?>
	</table>
	<?
}
?>
</body>
</html>
