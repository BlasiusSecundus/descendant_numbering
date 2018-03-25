<?php

class NGSQ extends DescendantNumberProviderBase {
    
    /**
     * A minimalistic roman numeral converter.
     * @param integer $int The input integer.
     * @return string The output roman numeral.
     */
    protected static function toRoman($int){
        switch($int){
            case 1: return "i";
            case 2: return "ii";
            case 3: return "iii";
            case 4: return "iv";
            case 5: return "v";
            case 6: return "vi";
            case 7: return "vii";
            case 8: return "viii";
            case 9: return "ix";
            case 10: return "x";
            case 20: return "xx";
            case 30: return "xxx";
        }
        
        if($int > 10 && $int < 40){
            if($int % 10 == 9){
                return NGSQ::toRoman($int - 9).NGSQ::toRoman(1).NGSQ::toRoman(10);
            }
            return NGSQ::toRoman($int - $int % 10).NGSQ::toRoman($int % 10);
        }
    }

    protected $IdCounter = 1;
    
    public function __construct(){}
    
    public function getCustomParameterDescriptors(){
        return array();//no custom parameters
    } 

    public function getDescendantNumber($params) {
        
        if(!$params) {
            return "1";
        }
        
        $this->IdCounter++;
        
        return $params->ParentNumber." ".$this->IdCounter.NGSQ::toRoman($params->NthChild);
         
    }

    public function getName() {
        return "NGSQ Style";
    }

    public function getSpouseNumber($other_spouse_number, $nth_marriage) {
        return null;//no spouse numbering
    }

}

