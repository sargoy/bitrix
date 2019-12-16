<?php

AddEventHandler("iblock", "OnAfterIBlockElementAdd", "OnAfterIBlockElementAddHandler");

// создаем обработчик события "OnBeforeIBlockElementAdd"
function OnAfterIBlockElementAddHandler(&$arFields)
{
    $ibe = new CIBlockElement;
    $ibp = new CIBlockProperty;
  
    $query = $ibe->GetList(
        array('created_date' => 'desc'),
        array('IBLOCK_ID' => $arFields['IBLOCK_ID'], 'ACTIVE' => 'Y',  'SECTION_CODE' => 'cover'),
        array('ID', 'IBLOCK_ID ', 'NAME')
    );

    if ($res = $query->Fetch()) {
        $period = $res['NAME'];
    }
       
    $res = CIBlock::GetProperties($arFields['IBLOCK_ID'], Array(), Array());
    if ($arr = $res->Fetch()) {
        //$arFields['PROPERTY_VALUES'] = array('MAGAZNUM' => $period);
        $ibp->Update($arr['ID'], array('DEFAULT_VALUE' => $period));
    }  

    if (142 == intval($arFields["IBLOCK_ID"])) {

        $arFile = CIBlockElement::GetList(
            array(),
            array('IBLOCK_ID' => 142, 'SECTION_ID' => 0),
            false,
            false,
            array('ID', 'IBLOCK_ID', 'PROPERTY_FILE')
        );

        if ($res = $arFile->Fetch()) {
            $imp_file = $_SERVER['DOCUMENT_ROOT'] . '/' . CFile::GetPath($res['PROPERTY_FILE_VALUE']);
        }

        if (!file_exists($imp_file) || !is_readable($imp_file)) {
            exit($imp_file . ' file NOT found');
        }

        // function to convert xml to php array
        function xml2Array($filename)
        {
            $xml  = simplexml_load_file($filename, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            return json_decode($json, true);
        }

        // function callback
        $arr  = xml2Array($imp_file);
        $data = array();
        $keys = array('num', 'stat');
        foreach ($arr as $value) {
            foreach ($value as $val) {
                if (is_array($val) && count($val) == 2) {
                    $data[] = array_combine($keys, $val);
                }
            }
        }

        //функция логирования событий
        function print_to_log($msg)
        {
            $file = $_SERVER['DOCUMENT_ROOT'] . '/admin/logs/' . date("d.m.Y") . '_bid.csv';
            file_put_contents($file, date("d.m.Y h:i:s") . mb_convert_encoding($msg, "Windows-1251", "UTF-8") . "\n", FILE_APPEND);
        }
        global $DB;
        $DB->Query("TRUNCATE TABLE b_bid");

        foreach ($data as $bid) {
            $arFields = array(
                'sab_num'  => "'" . trim((int)$bid['num']) . "'",
                // 'desc'     => $bid['stat'] == 1 ? 'Согласована' : 'Не согласована',
                'status'   => "'" . trim($bid['stat']) . "'",
                // 'd_status' => "'" . trim(date("d.m.Y")) . "'",
            );
            if ($bid['num']) {
                $DB->Insert("b_bid", $arFields, $err_mess . __LINE__);
            }
        }
    } else {
        AddMessage2Log("Ошибка добавления записи (".$arFields["RESULT_MESSAGE"].").");
    }
}