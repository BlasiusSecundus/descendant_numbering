<?php


/**
 * Collection of parameters used to generate descendant numberings.
 */
class DescendantNumberParameters
{
    /**
     *
     * @var integer Declares which child is the current person. This is a 1-based index number. If the person is the first child of his/her parent, this number is 1. If he/she is the second, this number is 2 and so on. Always the parent who is in the specified descendancy line will be take into account.
     */
    var $NthChild = 0;
    
    /**
     *
     * @var integer Declares which marriage of the parent the child belongs to. This is a 1-based index number. The first marriage is represented by the number 1 the second by 2 and so on.
     */
    var $NthMarr = 0;
    
    /**
     *
     * @var integer The number of total marriages of the parent.
     */
    var $NumTotalMarr = 0; 
    
    /**
     *
     * @var string The descendant numbering string of the parent of the current descendant. Use NULL for the first level of descendant.
     */
    var $ParentNumber = null;
    
    /**
     *
     * @var integer The "level" of the current child. This is a 1-based index. 1 = the children of the root ancestor, 2 = grandchildren and so on.
     */
    var $Level = 0;
}

/**
 * Enumeration of valid custom parameter types.
 */
final class CustomDescendantNumberParameterType
{
    /**
     * Single-line text.
     */
    const Text = "text";
    /**
     * Multiline text.
     */
    const TextMultiline = "text-multiline";
    /**
     * Dropdown list. (Select.)
     */
    const Dropdown = "dropdown";
    /**
     * Single choice list. (Radio buttons.)
     */
    const SingleChoice = "single-choice";
    /**
     * Multi-choice list. (Check boxes.)
     */
    const MultiChoice = "multi-choice";
    /**
     * Boolean. (Single checkbox).
     */
    const Boolean = "bool";
    /**
     * Integer number.
     */
    const Integer = "integer";
    /**
     * Float-point number.
     */
    const Real = "real";
}

/**
 * Represetns a custom (provider-specific) parameter. 
 */
class CustomDescendantNumberParameterDescriptor
{
    /**
     * Constructor.
     * @param string $name The identifier of the parameter. Internally, this name is used to reference the parameter.
     * @param string $disp_name The user-friendly display name of the parameter. This will be used in the Webtrees UI.
     * @param string $type The type of the parameter. One of the constants defined in the  CustomDescendantNumberParameterType class.
     * @param string $desc The description of the parameter. (Optional)
     * @param array $choices The array of possible choices. Only used by Dropdown, SingleChoice, MultiChoice parameters.
     */
    public function __construct($name, $disp_name,  $type, $desc = null, $choices = null) {
        $this->Name = $name;
        $this->DisplayName = $disp_name;
        $this->Description = $desc;
        $this->Type = $type;
        $this->Choices = $choices;
    }
    /**
     *
     * @var string The identifier of the parameter. Internally, this name is used to reference the parameter.
     */
    var $Name = "CustomParamName";
    /**
     *
     * @var string The user-friendly display name of the parameter. This will be used in the Webtrees UI. 
     */
    var $DisplayName = "Custom parameter";
    /**
     *
     * @var string The description of the parameter. (Optional)
     */
    var $Description = "Parameter description";
    
    /**
     *
     * @var string The type of the parameter. One of the constants defined in the  CustomDescendantNumberParameterType class.
     */
    var $Type = "";
    
    /**
     *
     * @var string[] The array of possible choices. Only used by Dropdown, SingleChoice, MultiChoice parameters. For integer type, "min" and "max" can be defined.
     */
    var $Choices = array();
    
    /**
     * Optional regex pattern to validate text input.
     * @var string
     */
    var $Pattern = NULL;
}

/**
 * Base class for descendant number provider.
 */
abstract class DescendantNumberProviderBase
{
    
    /**
     * Sets and validates a text (string) parameter.
     * @param CustomDescendantNumberParameterDescriptor $desc The parameter descriptor.
     * @param mixed $value The new value;
     */
    protected function setTextParam($desc, $value)
    {
        if(!is_a($desc, "CustomDescendantNumberParameterDescriptor"))
        {
            throw new Exception("Bad parameter descriptor procided.");
        }
        
        if(!in_array($desc->Type, [CustomDescendantNumberParameterType::Text, 
            CustomDescendantNumberParameterType::TextMultiline] )){
        
            throw new Exception("$desc->Name is not a text parameter.");
        }
        
        $str_val = string($value);
        
        if($desc->Pattern && !preg_match("/$desc->Pattern/g", $str_val))
        {
             throw new Exception("Invalid string pattern.");
        }

        $name = $desc->Name;
        $this->$name = $str_val;
    }
    
    
    /**
     * Sets and validates a "choice" parameter. That is, a parameter where the user should use from a preset of choices.
     * @param CustomDescendantNumberParameterDescriptor $desc The parameter descriptor.
     * @param mixed $value The new value;
     */
    protected function setChoiceParam($desc, $value)
    {
        if(!is_a($desc, "CustomDescendantNumberParameterDescriptor"))
        {
            throw new Exception("Bad parameter descriptor procided.");
        }
        
        if(!in_array($desc->Type, [CustomDescendantNumberParameterType::MultiChoice, 
            CustomDescendantNumberParameterType::SingleChoice,
            CustomDescendantNumberParameterType::Dropdown] )){
        
            throw new Exception("$desc->Name is not a choice parameter.");
        }
        
        if(!in_array($value, array_keys($desc->Choices)))
        {
            throw new Exception("Invalid choice: $value.");
        }

        $name = $desc->Name;
        $this->$name = $value;
    }
    
    /**
     * Sets and validates a custom numeric parameter.
     * @param CustomDescendantNumberParameterDescriptor $desc The parameter descriptor.
     * @param mixed $value The new value
     */
    protected function setNumericParam($desc, $value)
    {
        if(!is_a($desc, "CustomDescendantNumberParameterDescriptor"))
        {
            throw new Exception("Bad parameter descriptor procided.");
        }
        
        if(!in_array($desc->Type, [CustomDescendantNumberParameterType::Integer, 
            CustomDescendantNumberParameterType::Real] )){
        
            throw new Exception("$desc->Name is not a numeric parameter.");
        }
        
        $valfunc = $desc->Type ===  CustomDescendantNumberParameterType::Integer ? "intval" : "floatval";
        $val = $valfunc($value);
        
        if(isset($desc->Choices["min"]) && $val < $valfunc($desc->Choices["min"])) {
            throw new Exception("$val is smaller than the required minimum value: ".$valfunc($desc->Choices["min"]));
        }
        if(isset($desc->Choices["max"]) && $val > $valfunc($desc->Choices["max"])) {
            throw new Exception("$val is greater than the allowed maximum value: ".$valfunc($desc->Choices["max"]));
        }
 
        $name = $desc->Name;
        $this->$name = $val;
    }
    /**
     * Sets the value of the specified parameter.
     * @param string $name The name of the parameter.
     * @param mixed $value The value of the parameter.
     */
    public function setParameter($name, $value)
    { 
        if(!$name) 
        {
            return;
        }
       
       $my_desc = $this->getCustomParameterDescriptor($name);
       
       if(!$my_desc)
       {
           throw new Exception("Invalid parameter name: $name.");
       }
        
       switch($my_desc->Type)
       {
           case CustomDescendantNumberParameterType::Boolean:
               $this->$name = filter_var($value, FILTER_VALIDATE_BOOLEAN);
               break;
           case CustomDescendantNumberParameterType::Integer:
           case CustomDescendantNumberParameterType::Real:
               $this->setNumericParam($my_desc, $value);
               break;
           case CustomDescendantNumberParameterType::Dropdown:
           case CustomDescendantNumberParameterType::SingleChoice:
           case CustomDescendantNumberParameterType::MultiChoice:
               $this->setChoiceParam($my_desc, $value);
               break;
           case CustomDescendantNumberParameterType::Text:
           case CustomDescendantNumberParameterType::TextMultiline:
               $this->setTextParam($my_desc, $value);
               break;
           default:
               throw new Exception("Invalid parameter type.");
       }
       
    }
    
    /**
     * Sets the value multiple parameters.
     * @param array $parameters Associative array of key/value pairs.
     */
    public function setParameters($parameters)
    {
        if(!is_array($parameters))
        {
            return;
        }
        foreach($parameters as $name=>$value)
        {
            $this->setParameter($name, $value);
        }
    }
    
    /**
     * Gets the value of the custom parameter.
     * @param string $name The name of the parameter.
     * @return mixed The value of the parameter. NULL, if the parameter is not defined.
     */
    public function getCustomParameter($name)
    {
        
        $my_desc = $this->getCustomParameterDescriptor($name);
        
        if(!$my_desc)
        {
            throw new Exception("Invalid parameter name: $name.");
        }
        
        if(isset($this->$name)){
            return $this->$name;
        }
        
        return null;
    }
    
    /**
     * Gets the descriptor of a single custom parameter.
     * @param string $name The name of the parameter.
     * @return CustomDescendantNumberParameterDescriptor The descriptor of the current parameter. NULL, if no such parameter found.
     */
    public function getCustomParameterDescriptor($name)
    {
       foreach($this->getCustomParameterDescriptors() as $descriptor)
       {
           if($descriptor->Name === $name)
           {
               return $descriptor;
           }
       }
       
       return NULL;
    }
    
    /**
     * Gets the list of custom parameter descriptors.
     * @return CustomDescendantNumberParameterDescriptor[] The array of custom parameter descriptors. Returns and empty array, if no custom parameters are used.
     */
    public abstract function getCustomParameterDescriptors();
    
    
    /**
     * Gets the name of this numbering provider.
     * @return string The user-friendly name of the number provider. 
     */
    public abstract function getName();
    
    /**
     * Generates the spouse numbering based on input parameters.
     * @param type $other_spouse_number The number of the other spouse.
     * @param type $nth_marriage The 1-based intext telling which marriage is this.
     * @remarks Not all numbering styles include separate numbering for spouses. In these cases, this function should return NULL.
     * @return string The number of the spouse.
     */
    public abstract function getSpouseNumber($other_spouse_number, $nth_marriage);
    
    /**
     * Generates the descendant numbering based on the input parameters.
     * @param DescendantNumberParameters $params Parameters. If NULL, this function returns the number of the "root" ancestor.
     * @return string The number of the descendant.
     * @remarks If $param is NULL, this function should return the number for the "root" ancestor.
     *  */
    public abstract function getDescendantNumber($params);
}
