<?php

namespace WorkOfStan\mycmsprojectnamespace\AdminModels;

use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\LogMysqli;

/**
 * Translation management
 * Used by MyAdmin::controller()
 *
 * @author rejthar@stanislavrejthar.com
 */
class TranslationsAdminModel
{
    use \Nette\SmartObject;

    /** @var LogMysqli */
    protected $dbms = null;
    /** @ var string */
    //protected $tableName = 'instance';

    /**
     * Constructor, expects a Database connection
     * @param LogMysqli $dbms The Database object
     */
    public function __construct(LogMysqli $dbms)
    {
        $this->dbms = $dbms;
    }
