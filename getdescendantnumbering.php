<?php

use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Auth;



define('WT_SCRIPT_NAME', 'modules_v3/descendant_numbering/getdescendantnumbering.php');
require '../../includes/session.php';

require_once 'descendantnumbergenerator.php';

function echo_json($numberingstyle, $number_generator)
{
    header('Content-Type: text/json; charset=UTF-8');
    echo json_encode( [
    "numberingClass" =>[
        "id" => $numberingstyle,
        "name" => $number_generator->getNumberProvider()->getName()
    ],
    "numbering"=>$number_generator->getDescendantNumbering()
        ]);
}

function echo_csv($numberingstyle, $number_generator)
{
    header('Content-Type: text/csv; charset=UTF-8');
    $out = fopen('php://output', 'w');
    foreach($number_generator->getDescendantNumbering() as $indi=>$number){
        fputcsv($out, array($indi, $number));
    }
    fclose($out);
}

function get_post($variable){
    $value = Filter::get($variable);
    
    if(!$value)
    {
        $value = Filter::post($variable);
    }
    
    return $value;
}
/**
 * Defined in session.php
 *
 * @global Tree   $WT_TREE
 */
global $WT_TREE;
$tree_id = $WT_TREE->getTreeId();
$ancestor_xref = get_post("ancestor");

$numberingstyle = get_post("style");


$params = get_post("parameters");

$member = Auth::isMember($WT_TREE);


try{

if(!$member)    {
    throw new Exception(I18N::translate("The current user is not authorized to access this feature."));
}

if(!$numberingstyle) {
    throw new Exception(I18N::translate("Numbering style not provided."));
}
    
if(!$ancestor_xref) {
    throw new Exception(I18N::translate("Ancestor not provided."));
}
DescendantNumberProviderManager::setNumberingClassDirectory("numbering_classes");

$number_generator = new DescendantNumberGenerator($ancestor_xref,$numberingstyle,$params);    

$download = get_post("download");

$dl_format = get_post("dl-format");

if ($dl_format) {
    $dl_format = strtolower ($dl_format);
}

if($download)
{

    switch($dl_format){
        case "json":
            echo_json($numberingstyle, $number_generator);
            break;
        case "csv":
            echo_csv($numberingstyle, $number_generator);
            break;
        default:
            throw new Exception("Invalid download format: $dl_format");
    }
    
    header("Content-Disposition: attachment; filename=\"$ancestor_xref-$numberingstyle.$dl_format\"");
}
//ajax output
else {
    echo_json($numberingstyle, $number_generator);
}
}
catch(Exception $ex)
{
    echo json_encode([
        "error"=>[
            "message" => $ex->getMessage()
        ]
    ]);
}