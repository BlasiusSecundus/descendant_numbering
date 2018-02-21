<?php

class Henry extends DescendantNumberProviderBase
{
    public function __construct() {
       
    }
    
public function getCustomParameterDescriptors(){
    return array();
} 

public function getDescendantNumber($params) {
    
    if(!$params){
        return "1";
    }
    
    $retval = $params->ParentNumber;
    if($params->NthChild <= 9)
    {
        $retval.=$params->NthChild;
    }
    else if($params->NthChild === 10)
    {
        $retval.="X";
    }
    else {
        $retval.=chr(ord('A') + $params->NthChild - 11);
    }
    
    return $retval;
}

public function getName() {
    return "Henry System";
}

public function getSpouseNumber($other_spouse_number, $nth_marriage) {
    return NULL;
}

}