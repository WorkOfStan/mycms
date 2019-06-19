<?php

namespace GodsDev\mycmsprojectnamespace;

//use GodsDev\Tools\Tools;
//use Tracy\Debugger;
use GodsDev\MyCMS\MyAdmin;
use GodsDev\MyCMS\MyCMS;

class Admin extends MyAdmin
{

    use \Nette\SmartObject;

    /** @var array */
    protected $agendas = [];

    /** @var array */
    protected $ASSETS_SUBFOLDERS = [];

    /** @var array tables and columns to search in admin */
    protected $searchColumns = [
        'category' => ['id', 'category_#', 'description_#'], // "#" will be replaced by current language
        'content' => ['id', 'content_#', 'description_#'],
        'product' => ['id', 'product_#', 'description_#'],
    ];
    
    /**
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options overrides default values of properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        $this->clientSideResources['js'][] = 'scripts/Cookies.js';
        parent::__construct($MyCMS, $options);
        //Debugger::barDump($this->get, 'GET');
    }

    /**
     * As vendor folder has usually denied access from browser,
     * the content of the standard admin.css MUST be available through this method
     * 
     * @return string
     */
    public function getAdminCss()
    {
        return parent::getAdminCss() . PHP_EOL . file_get_contents(__DIR__ . '/../styles/admin.css') . PHP_EOL;
    }

}
