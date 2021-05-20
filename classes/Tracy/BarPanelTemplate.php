<?php

namespace WorkOfStan\MyCMS\Tracy;

use Tracy\IBarPanel;

/**
 * Tracy panel showing info about Template
 *
 */
class BarPanelTemplate implements IBarPanel
{

    /** @var string */
    protected $tabTitle;

    /** @var array<mixed> */
    protected $panelDetails;

    /** @var bool false = info, true = error */
    protected $errorPanel = false;

    /**
     *
     * @param string $tabTitle
     * @param array<mixed> $panelDetails
     */
    public function __construct($tabTitle, array $panelDetails)
    {
        $this->tabTitle = $tabTitle;
        $this->panelDetails = $panelDetails;
    }

    /**
     * Renders HTML code for custom tab.
     * @return string
     */
    public function getTab()
    {
        $style = $this->errorPanel ?
            'display: block;background: #D51616;color: white;font-weight: bold;margin: -1px -.4em;padding: 1px .4em;' :
            '';
        $icon = ''; // <img src="data:image/png;base64,<zakodovany obrazek>" />
        $label = '<span class="tracy-label" style="' . $style . '">' . $this->tabTitle . '</span>';
        return $icon . $label;
    }

    /**
     * Renders HTML code for custom panel.
     * @return string
     */
    public function getPanel()
    {
        $title = "<h1>{$this->tabTitle}</h1>";
        $warning = '';
        $cntTable = '';

        foreach ($this->panelDetails as $id => $detail) {
            $cntTable .= "<tr><td>{$id}</td><td> ";
            if (is_array($detail)) {
                $cntTable .= '<table>';
                foreach ($detail as $k => $v) {
                    $cntTable .= "<tr><td>{$k}</td><td title='"
                        . strip_tags(print_r($v, true))
                        . "'>" . substr(strip_tags(print_r($v, true)), 0, 240) . "</td></tr>";
                }
                $cntTable .= '</table>';
            } else {
                $cntTable .= print_r($detail, true);
            }
            $cntTable .= ' </td></tr>';
        }

        $content = '<div class=\"tracy-inner tracy-InfoPanel\"><table><tbody>' .
            $cntTable .
            '</tbody></table>* Hover over field to see its full content.</div>';

        return $title . $warning . $content;
    }

    /**
     * Set panel to be displayed as error.
     * If to be set to info again, try calling setError(false)
     *
     * @param bool $error OPTIONAL
     * @return void
     */
    public function setError($error = true)
    {
        $this->errorPanel = (bool) $error;
    }
}
