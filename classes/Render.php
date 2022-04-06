<?php

namespace WorkOfStan\MyCMS;

use Tracy\Debugger;
use WorkOfStan\MyCMS\Tracy\BarPanelTemplate;

/**
 * Class for a MyCMS object without translations.
 * This version has variable and methods for DBMS and result retrieval
 * and a rendering to a Latte templates.
 * For multi-language version of this class, use MyCMS.
 *
 * For a new project it is expected to make a extended class and place
 * additional attributes needed for running, then use that class.
 *
 */
class Render
{
    use \Nette\SmartObject;

    /** @var callable */
    private $customFilters;
    /** @var string */
    private $dirTemplateCache;
    /** @var string Latte template to load */
    private $template;
    /**
     * variables for template rendering
     * @ ar array<array<mixed>|false|int|null|string>
     */
    // public $context = [];

    /**
     * Constructor
     *
     * @param string $template
     * @ param array<array<mixed>|false|int|null|string> $context
     * @param string $dirTemplateCache
     * @param callable $customFilters
     */
    public function __construct($template //, array $context
        , $dirTemplateCache, $customFilters
        )
    {
        $this->template = $template;
//        $this->context = $context;
        $this->dirTemplateCache = $dirTemplateCache;
        $this->customFilters = $customFilters;
    }

    /**
     * Prefer project specific template over inherited template
     * TODO maybe use as filter for Latte
     *
     * @return string
     */
    private function getTemplateFile()
    {
        $projectTemplate = 'template/' . $this->template . '.latte';
        $inheritedTemplate = 'vendor/workofstan/mycms/' . $projectTemplate;
        return file_exists($projectTemplate) ? $projectTemplate : $inheritedTemplate;
    }

    /**
     * Latte initialization & Mark-up output
     *
     * @ param string $dirTemplateCache
     * @ param callable $customFilters
     * @param array<mixed> $params
     * @return void
     */
    public function renderLatte(//$dirTemplateCache, $customFilters,
        array $params)
    {
        // TODO context is maybe not necessary as everything is in params anyway
        Debugger::getBar()->addPanel(
            new BarPanelTemplate(
                'Template: ' . $this->template,
//                [
//                    'context' => $this->context,
//                    'params' =>
                    $params
//                ]
            )
        );
        if (isset($_SESSION['user'])) {
            Debugger::getBar()->addPanel(
                new BarPanelTemplate('User: ' . $_SESSION['user'], $_SESSION)
            );
        }
        $Latte = new \Latte\Engine();
        $Latte->setTempDirectory($this->dirTemplateCache);
        $Latte->addFilter(null, $this->customFilters);
        Debugger::barDump($params, 'Params');
        Debugger::barDump($_SESSION, 'Session'); // mainly for $_SESSION['language']
        $Latte->render($this->getTemplateFile(), $params); // @todo make it configurable
        unset($_SESSION['messages']);
        //$this->dbms->showSqlBarPanel();
    }
}
