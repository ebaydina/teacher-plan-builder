<?php

require_once "Modules/require.php";
error_reporting(E_ALL);
$filePdf = dirname(__DIR__) . '/Frontend/Concepts.pdf';
$fileHTML = dirname(__DIR__) . '/Frontend/Concepts.html';
/*
if(!file_exists($filePdf)){
    echo 'file pdf no exists';
    die;
}
if(file_exists($fileHTML)){
    @unlink($fileHTML);
}
shell_exec('pdf2txt.py -t html -o '. $fileHTML . ' ' . $filePdf);
if(file_exists($fileHTML)){

} */

$html = file_get_contents($fileHTML);
preg_match_all("/<div(.*?)>(.*?)<\/div>/muis", $html, $matches);
$tableName = '';
$data = [];
foreach ($matches[2] as $row) {
    if (mb_strpos($row, '<a name="1">Page') !== false) {
        continue;
    }
    $row = str_replace('<br>', "\n", $row);
    $row = strip_tags($row);
    $shortVowelsSearch = 'Short Vowels:';
    if (mb_strpos($row, $shortVowelsSearch) !== false) {
        $tableName = 'Shor Vowels';
        $array = explode($shortVowelsSearch, $row);
        $row = array_pop($array);
    }
    $row = trim($row);
    if (!isset($data[$tableName])) {
        $data[$tableName] = [];
    }
    $data[$tableName][] = $row;
}
print_r($data);
/*
$html = str_replace("<br>", "|", $html);
$html = str_replace(">", ">№", $html);
$text = strip_tags($html);
$text = str_replace('|№', '№', $text);
$text = str_replace('№№№№', '№№', $text);
$text = str_replace('№№№', '№№', $text);
$text = str_replace('№:', ':', $text);
$rows = explode("№№", $text);
$tableName = '';
$tables = [
    "Short Vowels:" => [
        "columns" => [
            "Short a",
            "Short i",
            "Short e",
            "Short o",
            "Short u"
        ],
        "name" => "Short Vowels"
    ],
    "Long Vowels (v_e):" => [
        "columns" => [
            "Long a",
            "Long i",
            "Long e",
            "Long o",
            "Long u"
        ],
        "name" => "Long Vowels"
    ],
    "Sight words > PreK:" => [
        "name" => "Sight Words"
    ]
];
$data = [];
foreach($rows as $row){
    $row = htmlspecialchars_decode($row);
    if(mb_strpos($row, ':') !== false){
        $tableName = trim($row);
        if($tableName == 'Page:'){
            break;
        }
        continue;
    }
    if($tableName){
        if(!isset($data[$tableName])){
            $data[$tableName] = [];
        }
        if(trim($row) == ""){
            $row = "";
        }else{
            $row = str_replace("|", "\n", $row);
            $row = trim($row);
            $row = str_replace("\n", "|", $row);
            $row = str_replace("|", "<br>", $row);
            if(mb_strpos($row, 'Page ') !== false){
                continue;
            }
        }
        $data[$tableName][] = $row;
    }
}
$trueData = [];
foreach($data as $table => $rows){
    $tableName = $tables[$table]['name'] ?? $table;
    if(!isset($trueData[$tableName])){
        $trueData[$tableName] = [];
    }
    $tableColumns = $tables[$table]['columns'] ?? [];
    $countTableColumns = count($tableColumns);
    $rows = $countTableColumns > 0 ? array_chunk($rows, count($rows) / $countTableColumns) : $rows;
    if(count($tableColumns)) {
        foreach ($tableColumns as $k => $columnName) {
            $columnName = trim($columnName);
            if (!isset($trueData[$tableName][$columnName])) {
                $column = [];
                foreach (array_column($rows, $k) as $columnValue) {
                    $columnValue = trim($columnValue);
                    if($columnName == $columnValue){
                        continue;
                    }
                    if (mb_strlen($columnValue)) {
                        $column[] = $columnValue;
                    }
                }
                $trueData[$tableName][$columnName] = $column;
            }
        }
    }else{
        $trueRows = [];
        foreach($rows as $row){
            $row = trim($row);
            if(mb_strlen($row)){
                $trueRows[] = $row;
            }
        }
        $trueData[$tableName] = $trueRows;
    }
}
print print_r($trueData, true); */
