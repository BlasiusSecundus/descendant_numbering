<?php

require_once 'idescendantnumberprovider.php';
require_once 'descendantnumberprovidermanager.php';

use Fisharebest\Webtrees\Individual;  

/**
 * Generates descendant numbering for an ancestor individual.
 */
class DescendantNumberGenerator
{
    /**
     * 
     * @var Individual The ancestor.
     */
    protected $Ancestor = null;
    
    /**
     * @var IDescendantNumberProvider The descendant number provider.
     */
    protected $NumberProvider = null;
    
    /**
     * Descendant numbering. Key = descendant XREF (string value), value = descendant numbering.
     * @var string[] 
     */
    protected $DescendantNumbering = [];
    
    /**
     * Generates descendant numbering for the specified ancestor.
     * @param Individual $ancestor The ancestor.
     * @param IDescendantNumberProvider $numbering_class The numbering style.
     * @param Individual[] $numbering The array of numbered individuals. Used internally for recursion. Must be an empty array when initially called with the ancestor.
     * @return string[]
     */
    protected static function getDescendantNumberingFor($ancestor, $numbering_class, &$numbering = array())
    {
      
        $children = [];
        
        $spouse_families = $ancestor->getSpouseFamilies();
        
        usort($spouse_families,array("Fisharebest\Webtrees\Family","compareMarrDate"));
        
        $cumulative_child_idx = 0;
        
        if(!$numbering)//if the numbering array is empty, the current $ancestor is the "root"
        {
            $numbering[$ancestor->getXref()] = $numbering_class->getDescendantNumber(NULL);
        }
        
        //spouse numbering - if applicable
        for($f = 0; $f <count($spouse_families); $f++)
        {
            //get spouse number
            $spouse_number = $numbering_class->getSpouseNumber($numbering[$ancestor->getXref()], $f+1);
            
            if($spouse_number === NULL) {continue;}//do not add if numbering not provided
            
            //get the spouse of the ancestor
            $fam = $spouse_families[$f];
            $wife = $fam->getWife(); 
            $husband = $fam->getHusband();
            
            if($wife == $ancestor)  //ancestor is the wife => her spouse is the husband
            { 
                $spouse = $husband; 
                
            }
            else if($husband == $ancestor) //ancestor is the husband => his spouse is the wife
            {
                 $spouse = $wife;
            }
            
            else 
            {
                $spouse = NULL;
            }
          
            //add spouse number
            if($spouse === NULL){

                $numbering["SPOUSE-NULL-{$ancestor->getXref()}-{$fam->getXref()}-{$ancestor->getTree()->getName()}-{$fam->getTree()->getName()}"] = $spouse_number;
                //throw new Exception("Failed to retrieve the spouse of {$ancestor} (family: $fam).");
            }
            else{
                $numbering [$spouse->getXref() ] = $spouse_number; 
            }
            
        }
        
        //descendant numbering
        for($f = 0; $f<count($spouse_families); $f++) {
            
            
            $fam = $spouse_families[$f];
            $children = $fam->getChildren();
            
            usort($children,array("Fisharebest\Webtrees\Individual","compareBirthDate"));

            

            for($i = 0; $i<count($children); $i++)
            { 
                
                $params = new \DescendantNumberParameters();
                $params->NthChild = $cumulative_child_idx+$i+1;
                $params->NthMarr = $f+1;
                $params->NumTotalMarr = count($spouse_families);
                $params->ParentNumber = $numbering[$ancestor->getXref()];
                
                if(!$children[$i])
                {
                    $numbering["child-$i-of-$ancestor"] = $numbering_class->getDescendantNumber($params);
                    continue;
                }
                else {
                    $numbering[$children[$i]->getXref()] = $numbering_class->getDescendantNumber($params);
                }
                
                self::getDescendantNumberingFor($children[$i], $numbering_class,$numbering);
            }
            
            $cumulative_child_idx+=count($children);
        
        }
        
        return $numbering;
    }
    
    /**
     * Constructor.
     * @param Individual $ancestor
     * @param IDescendantNumberProvider $number_provider
     * @param array $parameters Custom parameters for the number provider.
     */
    public function __construct($ancestor, $number_provider,$parameters) {
        $this->setAncestor($ancestor);
        $this->setNumberProvider($number_provider,$parameters);
    }
    
    /**
     * Sets the descendant number provider.
     * @param IDescendantNumberProvider $number_provider
     * @param array $parameters Custom parameters for the number provider.
     */
    public function setNumberProvider($number_provider,$parameters)
    {
        if(is_a($number_provider,"IDescendantNumberProvider"))
        {
            $this->NumberProvider = $number_provider;
        }
        
        else {
            $this->NumberProvider = DescendantNumberProviderManager::getProviderByClassName($number_provider);
        }
        
        $this->NumberProvider->setParameters($parameters);
    }
    /**
     * Gets the current number provider.
     * @return IDescendantNumberProvider
     */
    public function getNumberProvider()
    {
        return $this->NumberProvider;
    }
    
    /**
     * Sets the ancestor.
     * @global Tree $WT_TREE
     * @param Individual $ancestor The ancestor.
     */
    public function setAncestor($ancestor)
    {
        if(is_a($ancestor, "Fisharebest\Webtrees\Individual"))
        {
            $this->Ancestor = $ancestor;
        }
        
        else  { 
            global $WT_TREE;
            $this->Ancestor = Individual::getInstance ($ancestor, $WT_TREE);
        }
        
        $this->DescendantNumbering = [];
    }
    
    /**
     * Gets the descendant numbering for the descendants of the specified ancestor, using the specified numbering style.
     * @return string[]
     */
    public function getDescendantNumbering()
    {
        if($this->DescendantNumbering)
        {
            return $this->DescendantNumbering;
        }
        
        return self::getDescendantNumberingFor($this->Ancestor, $this->NumberProvider, $this->DescendantNumbering);
    }
}

