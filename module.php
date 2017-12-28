<?php

namespace BlasiusSecundus\WebtreesModules\DescendantNumbering;

use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleTabInterface;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Auth;
use Composer\Autoload\ClassLoader;

require_once 'descendantnumberprovidermanager.php';
require_once 'descendantnumbergenerator.php';

class DescendantNumberingModule extends AbstractModule implements ModuleTabInterface
{
    
    /** @var string prefix for descendant numbering fact names */
    var $FactNamePrefix = "BS_DESC_NO_";
    
    /** @var string prefix for individuals that serve as root (ancestor) of a descendant numbering line */
    var $FactRootNamePrefix = "BS_DESC_NO_ROOT";
    
    /** @var string[] registered descendant numbering classes. If null, call getDescendantNumberingClasses to retrieve them.*/
    var $DescendantNumberingClasses = null;
    
    /** @var string location of the branch export module files */
    var $directory;
    
    /**
     * Gets the already recorded numbering facts for the currently selected individual.
     * @global type $controller The controller
     * @return array The list of fact names.
     */
    protected function getNumberingFactNames()
    {   
       
       global $controller;
       
       $ancestor = $controller->getSignificantIndividual();
       
       if(!$ancestor)   { return array(); }
       
       $facts = $ancestor->getFacts();
       
       $desc_num_fact_names = [];
       
       foreach($facts as $fact)
       {
           if(strpos($fact->getTag(), $this->FactRootNamePrefix) === 0)
           {
               $desc_num_fact_names[] = $fact->getValue();
           }
       }
       
       return  $desc_num_fact_names;
    }
    
    /**
     * Prints the parameter controls for the specified parameter.
     * @param \CustomDescendantNumberParameterDescriptor $parameter_descriptor
     */
    protected function getCustomParameterControls($parameter_descriptor)
    {
        $retval = "";
       
        switch($parameter_descriptor->Type)
        {
            case \CustomDescendantNumberParameterType::Boolean:
                 $ctrl_id = bin2hex(random_bytes(8));
                $retval.="<td class=\"facts_label\"><label for='$ctrl_id' title=\"".htmlspecialchars($parameter_descriptor->Description)."\">".htmlspecialchars($parameter_descriptor->DisplayName)."</label></td>"
                        ."<td class=\"facts_value\"><input type='checkbox' name=\"".htmlspecialchars($parameter_descriptor->Name)."\" id='$ctrl_id'></td>";
                break;
            case \CustomDescendantNumberParameterType::SingleChoice:
                
                $retval.="<td class=\"facts_label\">".htmlspecialchars($parameter_descriptor->DisplayName)."</td>";
                $retval.="<td class=\"facts_value\">";
                $is_first = true;
                foreach($parameter_descriptor->Choices as $value=>$display_name)
                {
                     $ctrl_id = bin2hex(random_bytes(8));
                    $retval.="<br><input type='radio' name=\"".htmlspecialchars($parameter_descriptor->Name)."\" id='$ctrl_id' value='$value' ".($is_first?"checked":"")."><label for='$ctrl_id'>$display_name</label>";
                    $is_first = false;
                }
                $retval.="</td>";
                break;
            case \CustomDescendantNumberParameterType::Integer:
                $ctrl_id = bin2hex(random_bytes(8));
                $retval.="<td class=\"facts_label\"><label for='$ctrl_id' title=\"".htmlspecialchars($parameter_descriptor->Description)."\">".htmlspecialchars($parameter_descriptor->DisplayName)."</label></td>";
                $retval.="<td class=\"facts_value\">";
                $retval.="<input name='".htmlspecialchars($parameter_descriptor->Name)."' id='$ctrl_id' type='number' ";
                if(isset($parameter_descriptor->Choices["min"]))
                    $retval.=" min='".intval($parameter_descriptor->Choices["min"])."' ";
                if(isset($parameter_descriptor->Choices["max"]))
                    $retval.=" max='".intval($parameter_descriptor->Choices["max"])."' ";
                $retval.="/>";
                $retval.="</td>";
                break;
        }
        
        return $retval;
    }
    
    public function __construct() {
        
        parent::__construct('descendant_numbering');
        $this->directory = WT_MODULES_DIR . $this->getName();
        \DescendantNumberProviderManager::setNumberingClassDirectory($this->directory."/numbering_classes");
        $this->action = Filter::get('mod_action');

        // register the namespaces
        $loader = new ClassLoader();
        $loader->addPsr4('BlasiusSecundus\\WebtreesModules\\DescendantNumbering\\', $this->directory);
        $loader->register();
        
        //$this->init();
    }
    
    public function getDescription()
    {
        return I18N::translate("Generates descendant numbering in various formats, and saves it as a Gedcom fact.");
    }
    
    public function getTitle()
    {
        return I18N::translate("Descendant numbering");
    }
    
    public function getName()
    {   
        return "descendant_numbering";
    }
    
    public function defaultAccessLevel() {
        return Auth::PRIV_USER;
    }
    
    public function isGrayedOut() {
        return false;
    }
    
    public function getTabContent() {
        
         global $controller;
         $controller
                ->addExternalJavascript($this->directory."/assets/descendant_numbering.js");
         
         $ancestor = $controller->getSignificantIndividual();
        
        $fact_names = $this->getNumberingFactNames();
        $numbering_classes = \DescendantNumberProviderManager::getDescendantNumberingClasses();
        
        $retval = "<table class=\"facts_table\">"
                . "<tr><td class=\"descriptionbox\">"
                . "Select a numbering style:"
                . "<input type='hidden' id='numbering-ancestor' value='{$ancestor->getXref()}'>" 
                . "<select id='numbering-styles'>";
                
        foreach($numbering_classes as $numclass)
        {
            $retval.="<option value='".get_class($numclass)."'>{$numclass->getName()}</option>";
        }
        
        $retval.= "</select>"
                 . "<button id='preview-numbering'>Preview</button> - Download: "
                 . "<button class='desc-num-download' id='download-json' data-dl-format='JSON'>JSON</button> "
                 . "<button class='desc-num-download' id='download-csv' data-dl-format='CSV'>CSV</button> "
                . "</td></tr>"
                . "</table>";
        
        foreach($numbering_classes as $numclass)
        {
            if(!$numclass->getCustomParameterDescriptors()) {continue;}
            
            $retval .= "<table id=\"". get_class($numclass)."-parameters\" data-numbering=\"".get_class($numclass)."\" class=\"facts_table custom-descendant-numbering-parameters\">";
            
            foreach($numclass->getCustomParameterDescriptors() as $paramdesc)
            {
                $retval.="<tr>";
                $retval.=$this->getCustomParameterControls($paramdesc);
                $retval.="</tr>";
            }
            $retval.="</fieldset>";
        }
        $retval.= "<table class='facts_table' style='width: 100%' id='numbering-preview'>"
                
                . "<caption class='subheaders'>Descendant numbering - <span id='numbering-preview-numbering-style'></span></caption>"
                
                . "<thead> "
                . "<tr> "
                . "<td class='facts_label'>XREF</td> "
                . "<td class='facts_label'>Name</td>"
                . "<td class='facts_label'>Number</td> "
                . "</tr> "
                . "</thead>"
                
                . "<tbody>"
                . "</tbody>"
                
                . "</table>"
                ."<table class='facts_table' style='width: 100%' id='null-spouses'>"
                ."<caption class='subheaders'>".I18N::translate("The spouses of the following individuals could not be loaded")."</caption>"
                ."<thead>"
                ."<tr>"
                ."<td class='facts_label'>".I18N::translate("Individual")."</td>"
                ."<td class='facts_label'>".I18N::translate("Family")."</td>"
                ."<td class='facts_label'>".I18N::translate("Spouse number")."</td>"
                ."</tr>"
                ."</thead>"
                ."<tbody></tbody>"
                ."</table>";
        
        
        return $retval;
    }
    
    public function defaultTabOrder(){
        return 1;
    }
    
    public function canLoadAjax()
    {
        return false;
    }
    
    public function getPreLoadContent() {
        return "";
    }
    
    public function hasTabContent() {
        return Auth::user()->getUserId() > 0; 
    }
    
}

return new DescendantNumberingModule();