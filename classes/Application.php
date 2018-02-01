<?php
namespace GodsDev\MyCMS;

//use GodsDev\Tools\Tools;

class Application {
 
    /**
     *
     * @var array
     */
    protected $options = array();
    

    /**
     * All the necessary settings of MyCMS application
     * 
     * @param array $options that overides default values within constructor
     */
    function __construct(array $options = array())
    {
        $this->options = array_merge(                
                array(//default values
                ),
                $options);
        
    }
        
    /**
     * Run application and output mark-up
     */
    function run()
    {
        
    }
    
    
}
