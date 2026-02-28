<?php

namespace Core;

use Core\Db;
use Core\Gui;
use Core\Date;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RedBeanPHP\RedException\SQL;
use Throwable;
use morphos\Russian\Cases;
use morphos\Russian\RussianLanguage;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function morphos\Russian\getNameCases;
use function morphos\Russian\inflectName;
use morphos\Russian\FirstNamesInflection;

// Для имён
use morphos\Russian\LastNamesInflection;

// Для фамилий
use morphos\Russian\MiddleNamesInflection;

// Для отчеств
use morphos\Russian\GeographicalNamesInflection;
use morphos\Russian\NounDeclension;

class Templates
{

    /**
     * @var \Core\Db
     */
    private /*array*/
        $_get, $_post, $_session, $_cookie, $_server;
    private /*R*/
        $rb;
    private $db;
    /**
     * @var string[]
     */
    public $props_array;
    /**
     * @var \Core\Gui
     */
    private $gui;
    /**
     * @var string[]
     */
    private $month;
    /**
     * @var string
     */
    public $emblem;
    /**
     * @var \Twig\Environment
     */
    private $twig;
    public $bottom_logo;

    public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_session = $_SESSION;
        $this->_server = $_SERVER;
        $this->_cookie = $_COOKIE;
        $this->rb = new R();
        $this->db = new Db();
        $this->gui = new Gui();
        $this->date = new Date();
        $this->month = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря'
        ];
        $this->emblem = base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/images/moscow-region.png'));
        $this->bottom_logo = base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/images/pdf_logo.png'));

        $loader = new ArrayLoader([]);
        $this->twig = new Environment($loader);
    }

    /**
     * Склоняет слово или часть ФИО в родительный падеж.
     * @throws \Exception
     */
    public function fioToGenitive(?string $fio, ?string $case)
    {
        $forms = getNameCases($fio); //print_r($froms);
        return $forms[$case];
    }

    /**
     * Склоняет существительное в родительный падеж
     * @throws \Exception
     */
    public function nounToGenitive(?string $noun, ?string $case): string
    {
        $forms = NounDeclension::getCases($noun); //print_r($forms);
        return $forms[$case];
    }

    /**
     * @throws \Exception
     */
    public function phraseToGenitive(string $phrase, $case): string
    {
        $phraseArr = $this->splitByQuotes($phrase);
        $partsBefore = explode(' ', $phraseArr['before']);
        $partsAfter = explode(' ', $phraseArr['after']);
        $resultBefore = [];
        $resultAfter = [];

        foreach ($partsBefore as $part) {
            $resultBefore[] = $this->nounToGenitive($part, $case);
        }

        foreach ($partsAfter as $part) {
            $resultAfter[] = $this->nounToGenitive($part, $case);
        }

        return implode(' ', $resultBefore)
            . ' «' . $phraseArr['inside'] . '» '
            . implode(' ', $resultAfter);
    }

    /**
     * Разделяет строку на три части (до кавычек, внутри, после)
     * @param string $text
     * @return array|string[]
     */
    public function splitByQuotes(string $text): array
    {
        $pattern = '/^(.*?)(?:«|&laquo;|")(.*?)(?:»|&raquo;|")(.*)$/ui';
        if (preg_match($pattern, $text, $matches)) {
            return [
                'before' => trim($matches[1]), // До кавычек
                'inside' => trim($matches[2]), // Текст внутри кавычек
                'after' => trim($matches[3])  // После кавычек
            ];
        }
        return ['before' => $text, 'inside' => '', 'after' => ''];
    }

    /**
     * Составляет список проверяющих в виде массива строк
     * @param array $vars - массив данных о проверяющих
     * @param bool $with_head - признак включать в список руковдителя проверки или нет
     * @return array
     * @throws \Exception
     */
    public function buildListExecutors(array $vars, bool $with_head = false): array
    {
        $out = [];
        $i = 1;
        foreach ($vars as $var) {
            if ($var['executor_fio']['is_head'] && $with_head) {
                $out[0] = $this->fioToGenitive($var['executor_fio']) . ', ' . $this->nounToGenitive($var['executor_position']);
            }
            $out[] = $this->fioToGenitive($var['executor_fio']) . ', ' . $this->nounToGenitive($var['executor_position']);
            $i++;
        }

        return $out;
    }

    /**
     * Парсит шаблонную переменную с поддержкой обоих форматов:
     * 1. {{variable[param:value]}}
     * 2. {{variable[param]}}
     *
     * @param string $template
     * @return array|null ['variable', 'param', 'value'] (value будет null если нет двоеточия)
     */
    public function parseTemplateVariable(string $template): ?array
    {
        if (!preg_match('/^{{(\w+)\[([^\]]+)\]}}$/', $template, $matches)) {
            return null;
        }

        $parts = explode(':', $matches[2], 2);

        return [
            'variable' => $matches[1],
            'param' => $parts[0] ?? null,
            'value' => $parts[1] ?? null
        ];
    }

    /**
     * Обрабатывает текст, находя все шаблонные переменные
     * @throws \Exception
     */
    public function processTemplate(string $text, array $vars): string
    {
        $results = '';

        preg_match_all('/{{\w+\[[^\]]+\]}}/', $text, $matches);

        foreach ($matches[0] as $variable) {
            if ($data = $this->parseTemplateVariable($variable)) {
                if ($data['param'] == null) {
                    $results .= str_replace($variable, $vars[$data['variable']], $text);
                } else {
                    switch ($data['param']) {
                        case 'owner':
                            //для подписей
                            $results .= str_replace($variable,
                                $this->getSign($vars['signs'], $data['value']), $text
                            );
                            break;
                        case 'case':
                            //Для фраз и названий
                            $results .= str_replace($variable,
                                $this->nounToGenitive($vars[$data['variable']], $data['value']), $text
                            );
                            //Еще нужно для ФИО
                            break;
                    }
                }
            }
        }

        return $results;
    }

    public function getSign(?array $signData): string
    {
        $stamp = '';
        if (is_array($signData) && count($signData) > 0) {

            preg_match('/CN=([^,]+)/', $signData['subject'], $matches);
            $fullName = $matches[1] ?? null;

            $stamp = '
        <div class="signature-stamp" style="border: 2px solid #086a9b; padding: 1mm; position: relative; 
                width: 50mm; height: 17mm; font-family: DejaVu Sans, sans-serif; border-radius: 5px; background-color: #fff">
            <div class="signature-data" style="font-size: 5pt; line-height: 1; text-align: right">
                <img style="width: 5mm;float: left; z-index:100;" src="data:image/png;base64,' . $this->emblem . '">
                МИНИСТЕРСТВО СОЦИАЛЬНОГО РАЗВИТИЯ МОСКОВСКОЙ ОБЛАСТИ
            </div>
            <div class="signature-title" style="text-align: center; margin-top: 0.8mm; margin-bottom: 1mm; 
            font-size: 5pt; background-color: #086a9b; color: #fff; line-height: 8px; vertical-align: middle">
                СВЕДЕНИЯ О СЕРТИФИКАТЕ ЭП
            </div>
            <div class="signature-data" style="font-size: 6px; line-height: 1.2; text-align: left">
                <p style="margin: 0">Сертификат: ' . $signData['SerialNumber'] . '</p>
                <p style="margin: 0">Владелец: ' . $fullName . '</p>
                <p style="margin: 0">Действителен с ' . date('d.m.Y', strtotime($signData['validFrom']))
                . ' по ' . date('d.m.Y', strtotime($signData['validTo'])) . '</p></div>';
            // Если есть изображение подписи, добавляем его
            if (!empty($signData['sign_image'])) {
                $stamp .= '
                <div class="signature-image" style="text-align: center; margin: 5px 0;">
                    <img src="data:image/png;base64,' . base64_encode($signData['sign_image']) . '" style="max-width: 150px; max-height: 50px;">
                </div>';
            }

            $stamp .= '
                <!--div class="signature-date" style="text-align: center; font-size: 5pt; margin-top: 5px;">' . date('d.m.Y H:i:s') . '</div-->
            </div>
        ';
        }

        return $stamp;
    }

    public function getMonthName(int $monthNumber): string
    {
        return $this->month[$monthNumber];
    }

    /**
     * @throws \Exception
     */
    public function parse(string $html, array $vars, array $signData = []): string
    {
        //Штамп электронной подписи
        $stamp = '';
        //if($signData != []) {
        $stamp = '<div class="signature-stamp" style="position: fixed; border: 2px solid #3f65d5; padding: 10px; 
                width: 220px; height: 100px; font-family: DejaVu Sans, sans-serif; border-radius: 5px;">
                <div class="signature-data" style="font-size: 6pt; line-height: 1;">
                <img style="width: 40px;float: left; z-index:100" src="data:image/png;base64,' . $this->emblem . '" width="40">
                МИНИСТЕРСТВО СОЦИАЛЬНОГО РАЗВИТИЯ МОСКОВСКОЙ ОБЛАСТИ</div>
            <div class="signature-title" style="text-align: center; margin-top: 20px; margin-bottom: 0px; font-size: 7pt; background-color: #3f65d5; color: #fff">
            СВЕДЕНИЯ О СЕРТИФИКАТЕ ЭП</div>
            <div class="signature-data" style="font-size: 6pt; line-height: 0.5;">
                <p><b>Владелец сертификата:</b> ' . $signData['owner_name'] . '</p>
                <p><b>Серийный номер:</b> ' . $signData['certificate_number'] . '</p>
                <p><b>Срок действия:</b> ' . date('d.m.Y', strtotime($signData['certificate_valid_to'])) . '</p>';
        // Если есть изображение подписи, добавляем его
        if (!empty($signData['sign_image'])) {
            $stamp .= '
                <div class="signature-image" style="text-align: center; margin: 5px 0;">
                    <img src="data:image/png;base64,' . base64_encode($signData['sign_image']) . '" style="max-width: 150px; max-height: 50px;">
                </div>';
        }

        $stamp .= '
                <div class="signature-date" style="text-align: center; font-size: 5pt; margin-top: 5px;">' . date('d.m.Y H:i:s') . '</div>
            </div>
        </div>';
        //}

        if (strlen($html)) {
            $tags = [
                '{{header}}',
                '{{body}}',
                '{{bottom}}',
                '{{curr_moth}}',
                '{{plan_year}}',
                '{{curr_year}}',
                '{{today_date}}',
                '{{periods}}',
                '{{check_type}}',
                '{{check_period_start}}',
                '{{check_period_end}}',
                '{{executor_head[case:genitive]}}',
                '{{list_executors_without_head[case:genitive]}}',
                '{{institution}}',
                '{{institution[case:genitive]}}',
                '{{list_inspections}}',
                '{{sign}}'
            ];
            $replace = [
                $vars['header'],
                $vars['body'],
                $vars['bottom'],
                $this->getMonthName(date('n')),
                $vars['plan_year'],
                date('Y'),
                $this->date->dateToString(date('Y-m-d')) . ' года',
                $vars['periods'],
                $vars['check_type'],
                $vars['check_period_start'],
                $vars['check_period_end'],
                $this->fioToGenitive($vars['executor_head'], 'genitive') . ', ' . $this->nounToGenitive($vars['executor_head_position'], 'genitive'),
                $this->buildListExecutors($vars),
                $vars['institution'],
                $this->nounToGenitive($vars['institution'], 'genitive'),
                $vars['list_inspections'],
                $stamp
            ];

            return str_replace($tags, $replace, stripslashes($html));
        }
    }

    /**
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\LoaderError
     */
    public function twig_parse(?string $html, ?array $vars): string
    {
        if(strlen(trim($html)) > 0) {
            $template = $this->twig->createTemplate($html);
            return $template->render($vars);
        }else{
            echo '';
            return '';
        }
    }

}

