<?php

namespace WorkOfStan\mycmsprojectnamespace\AdminModels;

use GodsDev\Tools\Tools;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\L10n;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\MyCMS\MyTableAdmin;

/**
 * Translation management
 * Used by Admin::controller()
 *
 * @author rejthar@stanislavrejthar.com
 */
class TranslationsAdminModel
{
    use \Nette\SmartObject;

    /** @var LogMysqli */
    protected $dbms = null;
    /** @var string Folder and name prefix of localisation yml for the web UI (not admin UI) */
    protected $prefixUiL10n;
    /** @var MyTableAdmin */
    protected $tableAdmin;

    /**
     * Constructor, expects a Database connection
     * @param LogMysqli $dbms The Database object
     * @param MyTableAdmin $tableAdmin for the translate method
     */
    public function __construct(LogMysqli $dbms, MyTableAdmin $tableAdmin, $prefixUiL10n)
    {
        $this->dbms = $dbms;
        $this->tableAdmin = $tableAdmin;
        $this->prefixUiL10n = $prefixUiL10n;
    }

    /**
     * Displays table with all translations to be added and resaved
     *
     * @return string
     */
    public function htmlOutput()
    {
        $found = []; // translations found in latte templates
        foreach (glob('template/*.latte') as $file) {
            $tempFileContents = file_get_contents($file);
            Assert::string($tempFileContents);
            preg_match_all('~\{=("([^"]+)"|\'([^\']+)\')\|translate\}~i', $tempFileContents, $matches);
            $found = array_merge($found, $matches[2]);
        }
        $found = array_unique($found);
        $output = '<h1><i class="fa fa-globe"></i> ' . $this->tableAdmin->translate('Translations')
            . '</h1><div id="agenda-translations">'
            . '<form action="" method="post" onsubmit="return confirm(\''
            . $this->tableAdmin->translate('Are you sure?') . '\')">'
            . Tools::htmlInput('translations', '', 1, array('type' => 'hidden'))
            . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden')
            . Tools::htmlInput('old_name', '', '', array('type' => 'hidden', 'id' => 'old_name'))
            . '<table class="table table-striped"><thead><tr><th style="width:'
            . intval(100 / (count($this->tableAdmin->TRANSLATIONS) + 1)) . '%">'
            . Tools::htmlInput('one', '', false, 'radio') . '</th>';
        $translations = $keys = [];
        $localisation = new L10n($this->prefixUiL10n, $this->tableAdmin->TRANSLATIONS);
        foreach ($this->tableAdmin->TRANSLATIONS as $key => $value) {
            $output .= "<th>$value</th>";
            $translations[$key] = $localisation->readLocalisation($key);
            $keys = array_merge($keys, array_keys($translations[$key]));
        }
        $output .= '</tr></thead><tbody>' . PHP_EOL;
        $keys = array_unique($keys);
        natcasesort($keys);
        foreach ($keys as $key) {
            $output .= '<tr><th>'
                . Tools::htmlInput('one', '', $key, array('type' => 'radio', 'class' => 'translation')) . ' '
                . Tools::h((string) $key) . '</th>';
            foreach ($this->tableAdmin->TRANSLATIONS as $code => $value) {
                $output .= '<td>' . Tools::htmlInput(
                    "tr[$code][$key]",
                    '',
                    Tools::set($translations[$code][$key], ''),
                    ['class' => 'form-control form-control-sm', 'title' => "$code: $key"]
                ) . '</td>';
            }
            $output .= '</tr>' . PHP_EOL;
            if ($key = array_search($key, $found)) {
                unset($found[$key]);
            }
        }
        $output .= '<tr><td>' . Tools::htmlInput(
            'new[0]',
            '',
            '',
            array('class' => 'form-control form-control-sm', 'title' => $this->tableAdmin->translate('New record'))
        ) . '</td>';
        foreach ($this->tableAdmin->TRANSLATIONS as $key => $value) {
            $output .= '<td>' . Tools::htmlInput(
                "new[$key]",
                '',
                '',
                ['class' => 'form-control form-control-sm',
                        'title' => $this->tableAdmin->translate('New record') . ' (' . $value . ')']
            ) . '</td>';
        }
        $output .= '</tr></tbody></table>
            <button name="translations" type="submit" class="btn btn-secondary"><i class="fa fa-save"></i> '
            . $this->tableAdmin->translate('Save') . '</button>
            <button name="delete" type="submit" class="btn btn-secondary" value="1"><i class="fa fa-dot-circle"></i>
            <i class="fa fa-trash"></i> ' . $this->tableAdmin->translate('Delete') . '</button>
            <fieldset class="d-inline-block position-relative"><div class="input-group" id="rename-fieldset">'
            . '<div class="input-group-prepend">
              <button class="btn btn-secondary" type="submit"><i class="fa fa-dot-circle"></i> '
            . '<i class="fa fa-i-cursor"></i> ' . $this->tableAdmin->translate('Rename') . '</button>
            </div>'
            . Tools::htmlInput('new_name', '', '', array('class' => 'form-control', 'id' => 'new_name'))
            . '</div></fieldset>
            </form></div>' . PHP_EOL;
        $output .= count($found) ? '<h2 class="mt-4">' .
            $this->tableAdmin->translate('Missing translations in templates') . '</h2><ul>' : '';
        foreach ($found as $value) {
            $output .= '<li><code>' . Tools::h($value) . '</code></li>' . PHP_EOL;
        }
        $output .= count($found) ? '</ul>' : '';
        return $output;
    }
}
