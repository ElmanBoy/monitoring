<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/vendor/autoload.php'; // Установите и включите библиотеку CryptoPro CSP PHP SDK


/*use CryptoPro\CryptoPro;
use CryptoPro\CryptoProException;*/

error_reporting(E_ALL);
class CryptoProHelper
{
    public function Verify($input){
        $challenge = $input['challenge'];  // Данные, которые подписывались
        $signature = base64_decode($input['signature']);  // Подпись в бинарном виде
        $certData = base64_decode($input['certData']);  // Сертификат в DER-формате (base64)

        // 2. Открываем хранилище сертификатов
        //$store = new CPStore();
        $signedData = new CPSignedData();

        $certificate = new CPCertificate();
        $certificate->import($certData);

        $signedData->set_Content($challenge);
print_r($certData->isValid());
        $isValid = $signedData->VerifyCades($signature, CADES_BES, false); print_r($isValid);//$signedData->verify($signature, false, 0);

        if (!$isValid) {
            throw new Exception('Неверная подпись');
        }else {


            // Если все проверки пройдены
            $response = [
                'success' => true,
                'message' => 'Verification successful',
                /*'certInfo' => [
                    'subject' => $cert->get_SubjectName(),
                    'issuer' => $cert->get_IssuerName(),
                    'validFrom' => date('Y-m-d', $cert->get_ValidFromDate()),
                    'validTo' => date('Y-m-d', $cert->get_ValidToDate())
                ]*/
            ];
        }

        echo json_encode($response);
    }
}