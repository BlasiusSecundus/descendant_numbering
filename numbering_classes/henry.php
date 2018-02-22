<?php
use Fisharebest\Webtrees\I18N;

/**
 * Enumeration of d'Aboville custom parameter names.
 */
final class HenrySystemParameters
{
    /**
     * What version of the henry system (original or modified) is to be used.
     */
    const HenrySystemVersion="HenrySystemVersion";

}
class Henry extends DescendantNumberProviderBase
{
    public function __construct() {
       
    }
    
public function getCustomParameterDescriptors(){
    return [ new CustomDescendantNumberParameterDescriptor(
                    HenrySystemParameters::HenrySystemVersion,
                    I18N::translate("Henry System version"),
                    CustomDescendantNumberParameterType::SingleChoice,
                    I18N::translate("Select which version (original or modified) would you like to use."),
                    ["Original"=>I18N::translate("Original"),"Modified"=>I18N::translate("Modified")]
                    )];
} 

public function getDescendantNumber($params) {
    
    if(!$params){
        return "1.";
    }
    
    $use_modified = $this->getCustomParameter(HenrySystemParameters::HenrySystemVersion) === "Modified";
    
    $retval = str_replace(".", "", $params->ParentNumber);
    
    if($params->NthChild <= 9)
    {
        $retval.=$params->NthChild;
    }
    else if($params->NthChild === 10)
    {
        $retval.= $use_modified ? "($params->NthChild)" : "X";
    }
    else {
        $retval.= $use_modified ? "($params->NthChild)" : chr(ord('A') + $params->NthChild - 11);
    }
    
    $retval.=".";
    return $retval;
}

public function getName() {
    return "Henry System";
}

public function getSpouseNumber($other_spouse_number, $nth_marriage) {
    return NULL;
}

}