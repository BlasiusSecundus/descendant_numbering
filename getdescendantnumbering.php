<?php

use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Auth;



define('WT_SCRIPT_NAME', 'modules_v3/descendant_numbering/getdescendantnumbering.php');
require '../../includes/session.php';

require_once 'descendantnumbergenerator.php';

header('Content-Type: text/json; charset=UTF-8');
/**
 * Defined in session.php
 *
 * @global Tree   $WT_TREE
 */
global $WT_TREE;
$tree_id = $WT_TREE->getTreeId();
$ancestor_xref = Filter::post("ancestor");
if($ancestor_xref == NULL) {$ancestor_xref = Filter::get("ancestor");}

$numberingstyle = Filter::post("style");
if($numberingstyle == NULL) {$numberingstyle = Filter::get("style");}

$params = Filter::post("parameters");

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

$download = Filter::post("download");
if($download == NULL)
    $download = Filter::get("download");

if($download)
{
    
    header("Content-Disposition: attachment; filename='$ancestor_xref-$numberingstyle.json'");
}

echo json_encode( [
    "numberingClass" =>[
        "id" => $numberingstyle,
        "name" => $number_generator->getNumberProvider()->getName()
    ],
    "numbering"=>$number_generator->getDescendantNumbering()
        ]);
}
catch(Exception $ex)
{
    echo json_encode([
        "error"=>[
            "message" => $ex->getMessage()
        ]
    ]);
}