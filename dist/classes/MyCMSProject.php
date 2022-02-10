<?php

namespace WorkOfStan\mycmsprojectnamespace;

use GodsDev\Tools\Tools;
use Tracy\Debugger;
use Webmozart\Assert\Assert;
use WorkOfStan\Backyard\Backyard;
use WorkOfStan\Backyard\BackyardError;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\MyCMS\MyCMS;
use WorkOfStan\MyCMS\Tracy\BarPanelTemplate;
use WorkOfStan\mycmsprojectnamespace\Utils;

/**
 * Class for a MyCMS object.
 * It holds all specific variables needed for this application.
 * (Last MyCMS/dist revision: 2022-02-04, v0.4.5)
 */
class MyCMSProject extends MyCMS
{
    use \Nette\SmartObject;

    // attributes we need for this project

    /** @var array<string> */
    public $PAGES_SPECIAL;

    /** @var array<string> */
    public $SETTINGS;

    /** @var array<array<string|array<array<string>>>> */
    public $WEBSITE;

    /**
     * Constructor
     *
     * @param array<mixed> $myCmsConf
     */
    public function __construct(array $myCmsConf = [])
    {
        parent::__construct($myCmsConf);
    }

    /**
     * Output json or plain text json or error string and end execution
     *
     * @param bool $directJsonCall
     * @param Backyard $backyard for its JSON component
     * @param bool $humanReadable OPTIONAL (maybe it will be removed after the method piloting phase)
     * @return void
     */
    public function renderJson(
        $directJsonCall,
        Backyard $backyard,
        $humanReadable = false
    ) {
        if (array_key_exists('json', $this->context) && is_array($this->context['json'])) {
            // TODO remove after Controller.php refactor all context['message... to    Tools::addMessage('error',
            if (
                array_key_exists('messageSuccessInfo', $this->context) && !is_null($this->context['messageSuccessInfo'])
            ) {
                Assert::string($this->context['messageSuccessInfo']);
                Tools::addMessage('info', $this->context['messageSuccessInfo']);
            }
            if ($humanReadable) { // TODO: maybe it will be removed after the development phase
                echo Utils::niceDumpArray($this->context['json'], true);
            } else {
                // TODO Possible to remove context[$contextField]['success'] from Models?
                $this->context['json']['success'] = true; // so that AJAX snippet reloads page
                Utils::jsonOrEcho($this->context['json'], $directJsonCall, $backyard);
            }
        } elseif (array_key_exists('messageFailure', $this->context) && !is_null($this->context['messageFailure'])) {
            Debugger::barDump('contextJson is expected to be an array');
            // TODO remove after Controller.php refactor all context['message... to    Tools::addMessage('error',
            Assert::string($this->context['messageFailure']);
            Tools::addMessage('error', $this->context['messageFailure']);
            header('HTTP/1.1 404 Not Found', true, 404);
            echo($this->context['messageFailure']);
        } else {
            Debugger::barDump('contextJson is expected to be an array');
            header('HTTP/1.1 404 Not Found', true, 404);
        }

        $sqlStatementsArray = $this->dbms->getStatementsArray();
        if (!empty($sqlStatementsArray)) {
            $sqlBarPanel = new BarPanelTemplate('SQL: ' . count($sqlStatementsArray), $sqlStatementsArray);
            // TODO method_exists($this->dbms, 'getStatementsError') may be deleted with WoS/MyCMS v0.4.0
            if (
                method_exists($this->dbms, 'getStatementsError') && $this->dbms->getStatementsError() &&
                method_exists($sqlBarPanel, 'setError')
            ) {
                $sqlBarPanel->setError();
            }
            Debugger::getBar()->addPanel($sqlBarPanel);
        }
    }
}
