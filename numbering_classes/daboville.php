<?php


use Fisharebest\Webtrees\I18N;

/**
 * Enumeration of d'Aboville custom parameter names.
 */
final class dAbovilleParameters
{
    /**
     * Include spouses as separate entries.
     */
    const IncludeSpouses="includeSpouses";
    /**
     * Sets how to number 10th+ children.
     */
    const ChildrenNumberAfter10 = "childrenNumberAfter10";
    /**
     * Sets separator dot frequency. A separator dot is added after each N numbers.
     */
    const DotForEachNNumbers = "DotForEachNNumbers";
    
    /**
     * If true, a period after the first number will always be placed.
     */
    const DotAfterFirstNumber = "DotAfterFirstNumber";
}

/**
 * Implements descendant numbering based on the d'Aboville system.
 */
class dAboville implements IDescendantNumberProvider
{
    
    /**
     * Default constructor.
     */
    public function __construct() {
        
    }
    
    /**
     * {@inheritDoc}
     */
    public function setParameter($name, $value)
    {
        
        if(!$name) 
        {
            return;
        }
        
        $this->$name = $value;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setParameters($parameters)
    {
        if(!$parameters)
        {
            return;
        }
        foreach($parameters as $name=>$value)
        {
            $this->setParameter($name, $value);
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function getCustomParameter($name)
    {
        if(isset($this->$name)){
            return $this->$name;
        }
        
        return null;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getCustomParameterDescriptors()
    {
        return [
            new CustomDescendantNumberParameterDescriptor(
                    dAbovilleParameters::IncludeSpouses,
                    I18N::translate("Include spouses (a, b, c, ...)"),
                    CustomDescendantNumberParameterType::Boolean,
                    I18N::translate("Includes spouses as separate entries.")
                    ),
            new CustomDescendantNumberParameterDescriptor(
                    dAbovilleParameters::ChildrenNumberAfter10,
                    I18N::translate("Child numbering after 9"),
                    CustomDescendantNumberParameterType::SingleChoice,
                    I18N::translate("Sets how 10+ children are numbered."),
                    ["10"=>"Numbers (... 8, 9, 10, 11, ...)","A"=>"Capital letters (... 8, 9, A, B, ...)"]
                    ),
            new CustomDescendantNumberParameterDescriptor(
                    dAbovilleParameters::DotAfterFirstNumber,
                    I18N::translate("Place a period after first number:"),
                    CustomDescendantNumberParameterType::Boolean,
                    I18N::translate("Always places a separator period after the first number.")
                    ),
            new CustomDescendantNumberParameterDescriptor(
                    dAbovilleParameters::DotForEachNNumbers,
                    I18N::translate("Place a period after each Nth number:"),
                    CustomDescendantNumberParameterType::Integer,
                    I18N::translate("Sets how frequenly a separator period is placed. It is advisable to set \"Child numbering after 9\" to \"Capital letters\", if this value is greater than 2 and there are persons with more than 9 children."),
                    ["min"=>1]
                    ) 
        ];
    }
    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return "d'Aboville";
    }
    
    /**
     * {@inheritDoc}
     */   
    public function getSpouseNumber($other_spouse_number, $nth_marriage)
    {
        if($this->getCustomParameter(dAbovilleParameters::IncludeSpouses)){
            return $other_spouse_number.chr(ord('a')-1+$nth_marriage);
        }
        
        return null;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getDescendantNumber($params) {

        if(!$params) { return "1"; }//root ancestor individual
        
        $local_child_no = (string)$params->NthChild;
        
        $childNumberAfter10 = $this->getCustomParameter(dAbovilleParameters::ChildrenNumberAfter10);
        
        if($childNumberAfter10 == 'A' && $params->NthChild >= 10)
        {
            $local_child_no = chr(ord('A')-10+$params->NthChild);
        }
        
        $retval = $params->ParentNumber ? "$params->ParentNumber" : "";
        
        $dot_frequency = intval($this->getCustomParameter(dAbovilleParameters::DotForEachNNumbers));
        $dot_frequency = max(1,$dot_frequency);
        
        $dot_after_first = intval($this->getCustomParameter(dAbovilleParameters::DotAfterFirstNumber));
        
        $place_dot = false;
        
        //always place dot after first number
        if ($dot_after_first)  {
            if ($params->Level == 1)
                $place_dot = true;
            //in this case, the interval is counted from the second number
            else if( ($params->Level - 1) % $dot_frequency == 0)
                $place_dot = true;
        }
        //only place the dot at the specified interval
        else if($params->Level % $dot_frequency == 0)
            $place_dot = true;
        
        if($place_dot)
            $retval.=".";
        
        $retval.=$local_child_no;
        
        return $retval;
    }
}
