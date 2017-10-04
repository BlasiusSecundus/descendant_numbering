<?php


use Fisharebest\Webtrees\I18N;

final class dAbovilleParameters
{
    const IncludeSpouses="includeSpouses";
    const ChildrenNumberAfter10 = "childrenNumberAfter10";
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
        
        return  ( $params->ParentNumber ? "$params->ParentNumber." : "" ).$local_child_no;
    }
}
