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

    /**
     * which Latte template to load
     * @var string
     */
    public $template;
    /**
     * variables for template rendering
     * @var array<array<mixed>|false|int|null|string>
     */
    public $context = [];

    /**
     * Constructor
     *
     * @param string $template
     * @param array<array<mixed>|false|int|null|string> $context
     */
    public function __construct($template, array $context)
    {
        $this->template = $template;
        $this->context = $context;
    }

    /**
     * Latte initialization & Mark-up output
     *
     * @param string $dirTemplateCache
     * @param callable $customFilters
     * @param array<mixed> $params
     * @return void
     */
    public function renderLatte($dirTemplateCache, $customFilters, array $params)
    {
        // TODO context is maybe not necessary as everything is in params anyway
        Debugger::getBar()->addPanel(
            new BarPanelTemplate(
                'Template: ' . $this->template,
                [
                    'context' => $this->context,
                    'params' => $params
                ]
            )
        );
        if (isset($_SESSION['user'])) {
            Debugger::getBar()->addPanel(
                new BarPanelTemplate('User: ' . $_SESSION['user'], $_SESSION)
            );
        }
        $Latte = new \Latte\Engine();
        $Latte->setTempDirectory($dirTemplateCache);
        $Latte->addFilter(null, $customFilters);
        Debugger::barDump($params, 'Params');
        Debugger::barDump($_SESSION, 'Session'); // mainly for $_SESSION['language']
        $Latte->render('template/' . $this->template . '.latte', $params); // @todo make it configurable
        unset($_SESSION['messages']);
        //$this->dbms->showSqlBarPanel();
    }
}
