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
     * @var string[] The array of possible choices. Only used by Dropdown, SingleChoice, MultiChoice parameters. 
     */
    var $Choices = array();
}

/**
 * Interface for descendant number provider.
 */
interface IDescendantNumberProvider
{
    
    /**
     * Sets the value of the specified parameter.
     * @param string $name The name of the parameter.
     * @param mixed $value The value of the parameter.
     */
    public function setParameter($name, $value);
    
    /**
     * Sets the value multiple parameters.
     * @param array $parameters Associative array of key/value pairs.
     */
    public function setParameters($parameters);
    
    /**
     * Gets the value of the custom parameter.
     * @param string $name The name of the parameter.
     * @return mixed The value of the parameter. NULL, if the parameter is not defined.
     */
    public function getCustomParameter($name);
    
    /**
     * Gets the list of custom parameter descriptors.
     * @return CustomDescendantNumberParameterDescriptor[] The array of custom parameter descriptors. Returns and empty array, if no custom parameters are used.
     */
    public function getCustomParameterDescriptors();
    
    /**
     * Gets the name of this numbering provider.
     * @return string The user-friendly name of the number provider. 
     */
    public function getName();
    
    /**
     * Generates the spouse numbering based on input parameters.
     * @param type $other_spouse_number The number of the other spouse.
     * @param type $nth_marriage The 1-based intext telling which marriage is this.
     * @remarks Not all numbering styles include separate numbering for spouses. In these cases, this function should return NULL.
     * @return string The number of the spouse.
     */
    public function getSpouseNumber($other_spouse_number, $nth_marriage);
    
    /**
     * Generates the descendant numbering based on the input parameters.
     * @param DescendantNumberParameters $params Parameters. If NULL, this function returns the number of the "root" ancestor.
     * @return string The number of the descendant.
     * @remarks If $param is NULL, this function should return the number for the "root" ancestor.
     *  */
    public function getDescendantNumber($params);
}
