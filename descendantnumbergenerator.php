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
     *
     * @var integer The current level. Level 1 = the ancestor, 2 = their children, 3 = grandchildren and so on. 
     */
    protected $CurrentLevel = 1;
    
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
     * 
     * @param array $numbering
     * @param Individual $indi
     * @param string $number
     */
    protected static function storeNumber(&$numbering, $indi, $number)
    {
        $numbering[$indi->getXref()] = array("name"=>$indi ? $indi->getAllNames()[$indi->getPrimaryName()]["fullNN"]: NULL, "number"=>$number);
    }
    
    /**
     * Generates descendant numbering for the specified ancestor.
     * @param Individual $ancestor The ancestor.
     * @param IDescendantNumberProvider $numbering_class The numbering style.
     * @param Individual[] $numbering The array of numbered individuals. Used internally for recursion. Must be an empty array when initially called with the ancestor.
     * @param integer level The current level of recursion (i. e. the depth of the descendant tree). 1 = the children of the root ancestor, 2 = grandchildren and so on. Used internally.
     * @return string[]
     */
    protected static function getDescendantNumberingFor($ancestor, $numbering_class, &$numbering = array(), $level = 1)
    {
      
        $children = [];
        
        $spouse_families = $ancestor->getSpouseFamilies();
        
        usort($spouse_families,array("Fisharebest\Webtrees\Family","compareMarrDate"));
        
        $cumulative_child_idx = 0;
        
        if(!$numbering)//if the numbering array is empty, the current $ancestor is the "root"
        {
            self::storeNumber($numbering, $ancestor, $numbering_class->getDescendantNumber(NULL));
        }
        
        //spouse numbering - if applicable
        for($f = 0; $f <count($spouse_families); $f++)
        {
            //get spouse number
            $spouse_number = $numbering_class->getSpouseNumber($numbering[$ancestor->getXref()]["number"], $f+1);
            
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

                $numbering["SPOUSE-NULL-{$ancestor->getXref()}-{$fam->getXref()}-{$ancestor->getTree()->getName()}-{$fam->getTree()->getName()}"] = array("number"=>$spouse_number, "name"=>NULL);
                //throw new Exception("Failed to retrieve the spouse of {$ancestor} (family: $fam).");
            }
            else{
                self::storeNumber($numbering, $spouse, $spouse_number);
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
                $params->ParentNumber = $numbering[$ancestor->getXref()]["number"];
                $params->Level = $level;
                
                if(!$children[$i])
                {
                    $numbering["child-$i-of-$ancestor"] = array("number"=>$numbering_class->getDescendantNumber($params), "name"=>NULL);
                    continue;
                }
                else {
                    self::storeNumber($numbering, $children[$i], $numbering_class->getDescendantNumber($params));
                }
                
                self::getDescendantNumberingFor($children[$i], $numbering_class,$numbering,$level+1);
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
        
        $this->CurrentLevel = 1;
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

