<?php

require_once 'idescendantnumberprovider.php';


/**
 * This class manages the installed descendant number provider classes.
 */
class DescendantNumberProviderManager
{
    /**
     * @var IDescendantNumberProvider[] Loaded descendant number provider classes.
     */
    protected static $DescendantNumberingClasses = [];
    
    /** @var string directory where numberic classes are installed*/
    protected static $NumberingClassDirectory = "/numbering_classes";
    
    /**
     * Extracts class names from a PHP file
     * @param string $filepath PHP file path.
     * @return string[] Classn names.
     * @remarks https://stackoverflow.com/questions/928928/determining-what-classes-are-defined-in-a-php-class-file (answer by Venkat D.)
     */
    protected static function file_get_php_classes($filepath) {
      $php_code = file_get_contents($filepath);
      $classes = self::get_php_classes($php_code);
      return $classes;
    }

    /**
     * Extracts class names from PHP code.
     * @param string $php_code PHP code.
     * @return string[] Class names.
     * @remarks https://stackoverflow.com/questions/928928/determining-what-classes-are-defined-in-a-php-class-file (answer by Venkat D.)
     */
    protected static function get_php_classes($php_code) {
      $classes = array();
      $tokens = token_get_all($php_code);
      $count = count($tokens);
      for ($i = 2; $i < $count; $i++) {
        if (   $tokens[$i - 2][0] == T_CLASS
            && $tokens[$i - 1][0] == T_WHITESPACE
            && $tokens[$i][0] == T_STRING) {

            $class_name = $tokens[$i][1];
            $classes[] = $class_name;
        }
      }
      return $classes;
    }
    
    /**
     * Sets the directory where descendant numbering classes are stored.
     * @param string $dirname The directory name
     * @throws Exception If the director name is invalid.
     * @remakrs Changing the directory will invalidate the currently loaded numbering class list.
     */
    public static function setNumberingClassDirectory($dirname)
    {
        if(!is_dir($dirname))
        {
            throw new Exception("$dirname is not a directory.");
        }
        
        self::$NumberingClassDirectory = $dirname;
        
        //emptying class list
        self::$DescendantNumberingClasses = [];
    }
    
    /**
     * Gets the directory where descendant numbering classes are stored.
     * @return string
     */
    public static function getNumberingClassDirectory()
    {
        return self::$NumberingClassDirectory;
    }
    
    /**
     * Gets the installed descendant numbering classes.
     * @return IDescendantNumberProvider[]
     */
    public static function getDescendantNumberingClasses()
    {
        
        if(self::$DescendantNumberingClasses)
        { return self::$DescendantNumberingClasses; }
        
        self::$DescendantNumberingClasses = [];
        
        foreach(scandir(self::$NumberingClassDirectory) as $filename)
        {
            if($filename == "." || $filename == "..")
            {continue;}
            
            $full_file_path = self::$NumberingClassDirectory."/$filename";
            
            if(!is_file($full_file_path))
            {
                continue;
            }
            $classes = self::file_get_php_classes($full_file_path);
            if(!$classes)
                { continue; }
            
            require_once $full_file_path;
            
            foreach($classes as $class) {
                $class_metadata = new \ReflectionClass($class);
                if(!$class_metadata->implementsInterface("IDescendantNumberProvider")) 
                    {continue;}
                    
                self::$DescendantNumberingClasses[] = $class_metadata->newInstance(null);
            }
        }
        
        
        return self::$DescendantNumberingClasses;
    }
    
    public static function getProviderByClassName($class_name)
    {
        $providers = self::getDescendantNumberingClasses();
        
        foreach($providers as $provider)
        {
            if(get_class($provider) == $class_name) {
                return $provider;
            }
        }
        
        return null;
    }
}
