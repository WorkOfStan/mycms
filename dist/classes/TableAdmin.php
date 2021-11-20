<?php

namespace WorkOfStan\mycmsprojectnamespace;

use GodsDev\Tools\Tools;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\MyCMS\MyTableAdmin;

/**
 * Project specific adaptations of database tables in Admin UI
 * (Last MyCMS/dist revision: 2021-05-20, v0.4.0)
 */
class TableAdmin extends MyTableAdmin
{
    use \Nette\SmartObject;

    /**
     *
     * @param LogMysqli $dbms database management system (e.g. new mysqli())
     * @param string $table table name
     * @param array<string|array<string>> $options
     */
    public function __construct(LogMysqli $dbms, $table, array $options = [])
    {
        parent::__construct($dbms, $table, $options);
        Assert::isArray($options['TRANSLATIONS']);
        $this->TRANSLATIONS = $options['TRANSLATIONS'];
        $translationFile = 'conf/l10n/admin-' . Tools::setifempty($_SESSION['language'], 'en') . '.yml';
        // The union operator ( + ) might be more useful than array_merge.
        // The array_merge function does not preserve numeric key values.
        // If you need to preserve the numeric keys, then using + will do that.
        // TODO/Note: TRANSLATION is based on A project, rather than F project.
        $this->TRANSLATION += file_exists($translationFile) ? Yaml::parseFile($translationFile) : [];
    }

    /**
     * Customize particular field's HTML of current $this->table
     *
     * @param string $field
     * @param string $value field's value
     * @param array<string> $record
     * @return bool|string - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customInput($field, $value, array $record = [])
    {
        $result = false;
        $fieldLang = '##';
        $fieldName = $field;
        if (substr($field, -3, 1) === '_' && in_array(substr($field, -2), array_keys($this->TRANSLATIONS))) {
            // Localised fields may have language independant behaviour, so switch below handles type dependant code
            // Language dependant conditions can however be added as well in separate switch
            $fieldLang = substr($field, -2); // language of the field
            $fieldName = substr($field, 0, -2) . '##';
        }
        switch (mb_substr($this->table, mb_strlen(TAB_PREFIX)) . "\\" . $fieldName) {
            //case "tableName\\fieldName": $result = ""; break; // SPECIMEN
            //
            // Selection list of parent_product_id
            // TODO try this template in dist
            case "product\\parent_product_id":
                $result = $this->outputForeignId(
                    "fields[$field]",
                    'SELECT p.id,product_' . DEFAULT_LANGUAGE . ',division_' . DEFAULT_LANGUAGE .
                    ' FROM ' . TAB_PREFIX . 'product p LEFT JOIN ' . TAB_PREFIX . 'division d ON p.division_id = d.id'
                    . ' WHERE IFNULL(p.parent_product_id, 0) = 0 ORDER BY d.sort,p.sort',
                    $value,
                    ['class' => 'form-control', 'exclude' => (int) Tools::set($_GET['where']['id'])]
                );
                break;

            // URL fields have btn-webalize button
            case "content\\url_##":
                $result = '<div class="input-group">'
                    . Tools::htmlInput(
                        "fields[$field]",
                        '',
                        $value,
                        ['class' => 'form-control input-url', 'id' => $field . $this->rand]
                    )
                    . '<span class="input-group-btn">'
                    // btn-webalize referes to listener in admin.js
                    // TODO webalize according to the first row on the page (i.e. name, not description) like admin?urls
                    . '<button type="button" class="btn btn-secondary btn-webalize"'
                    . ' data-url="' . Tools::h($field . $this->rand) . '"'
                    . ' data-name="' . Tools::h(
                        mb_substr($this->table, mb_strlen(TAB_PREFIX)) . '_' . mb_substr($field, -2) . $this->rand
                    ) . '"'
                    . ' data-table="' . Tools::h($this->table) . '"'
                    . ' title="' . $this->translate('Convert') . '">'
                    . '<i class="fa fa-adjust" aria-hidden="true"></i></button>'
                    . '</span></div>';
                break;

            // Selection list of parent_product_id
            // TODO try this template in dist
            case "category\\image":
            case "content\\image":
            case "product\\image":
                $result = '<div class="input-group">'
                    . Tools::htmlInput(
                        "fields[$field]",
                        '',
                        $value,
                        ['class' => 'form-control input-image', 'id' => $field . $this->rand]
                    )
                    . '<span class="input-group-btn">'
                    . '<button type="button" class="btn btn-secondary ImageSelector" data-target="#'
                    . Tools::h($field . $this->rand) . '" title="' . $this->translate('Select')
                    . '"><i class="fa fa-image" aria-hidden="true"></i></button>'
                    . '</span></div>';
                break;

            // Selection list of category path
            // TODO try this template in dist
            case "category\\path":
                $result = $this->translate('Parent category') . ':<br />'
                    . Tools::htmlInput('path-original', '', $value, 'hidden')
                    . '<select class="form-control" name="path-parent" id="path' . $this->rand . '"><option />';
                $rows = $this->dbms->fetchAll('SELECT path,category_' . $_SESSION['language'] . ' AS category FROM '
                    . TAB_PREFIX . 'category ORDER BY path');
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $result .= Tools::htmlOption(
                            $row['path'],
                            str_repeat(
                                '… ',
                                (int) max(strlen((string) $row['path']) / PATH_MODULE - 1, 0)
                            ) . $row['category'],
                            substr($value, 0, -PATH_MODULE),
                            Tools::begins((string) $row['path'], $value)
                        );
                    }
                }
                $result .= '</select>';
                break;

            // Selection list of relations to product
            // TODO try this template in dist
            case "content\\product_id":
                $result = '<select class="form-control" name="fields[product_id]" id="' . $field . $this->rand
                    . '"><option />';
                $rows = $this->dbms->fetchAll('SELECT p.id,category_' . $_SESSION['language'] . ' AS category,product_'
                    . $_SESSION['language'] . ' AS title FROM ' . TAB_PREFIX . 'product p LEFT JOIN '
                    . TAB_PREFIX . 'category c ON p.category_id = c.id ORDER BY c.path,p.sort');
                if (is_array($rows)) {
                    $tmp = null;
                    foreach ($rows as $row) {
                        if ($tmp != $row['category']) {
                            $result .= (is_null($tmp) ? '' : '</optgroup>') . '<optgroup label="'
                                . Tools::h($tmp = (string)$row['category']) . '">' . PHP_EOL;
                        }
                        $result .= Tools::htmlOption($row['id'], (string) $row['title'], $value) . PHP_EOL;
                    }
                    $result .= (is_null($tmp) ? '' : '</optgroup>');
                }
                $result .= '</select>';
                break;
        }
        return $result;
    }

    /**
     * Custom HTML showed after particular field (but still in the table row, in case of table display).
     *
     * @param string $field
     * @param string $value
     * @param array<string> $record
     * @return string HTML
     */
    public function customInputAfter($field, $value, array $record = [])
    {
        $result = '';
        switch ($this->table . "\\$field") {
            // Show link to image after sort field
            // TODO try this F template in dist
            case TAB_PREFIX . "content\\sort":
                $result .= '<div id="news-image">'
                    . '<label for="picture-' . $this->rand . '">(' . $this->translate('image') . '):'
                    . (Tools::set($record['type'], '') == 'news' && ($id = Tools::set($record['id'], '')) ?
                    ' <a href="' . DIR_ASSETS . 'news/' . $id
                    . '.jpg" target="_blank"><i class="fa fa-external-link"></i></a>' : '')
                    . '</label><br>' . PHP_EOL
                    . Tools::htmlInput(
                        'picture',
                        '',
                        '',
                        ['type' => 'file', 'class' => 'form-control mt-1', 'id' => 'picture-' . $this->rand]
                    )
                    . '</div>';
                break;
        }
        return $result;
    }

    /**
     * Custom saving of a record. Record fields are in $_POST['fields'], other data in $_POST['database-table']
     * TODO: the code in this method is based on A project, adapt to dist
     *
     * @return bool - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customSave()
    {
        if (!isset($_POST['record-save']) || !$this->authorized()) {
            return false;
        }
        // category.path - if admin changes the parent category (or picks it for a new record)
        if (
            isset($_POST['table'], $_POST['path-original'], $_POST['path-parent'], $_POST['fields']['id']) &&
            $_POST['table'] == TAB_PREFIX . 'category' && (!Tools::begins(
                $_POST['path-original'],
                $_POST['path-parent']
            ) || Tools::set($_POST['fields-null']['path']))
        ) {
            $length = [strlen($_POST['path-parent']), strlen($_POST['path-original'])];
            // existing record whose parent category changed - we need to shift its sibblings
            // that follow after it to fill the gap
            if ($_POST['path-original'] && $_POST['fields']['id'] != '') {
                // we can't allow for other admin to write into categories during this op
                $this->dbms->query('LOCK TABLES ' . Tools::escapeDbIdentifier($_POST['table']) . ' WRITE');
                //category.path is a unique key so to allow for the following change we need to set this path to NULL
                $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                    . ' SET path = NULL WHERE id=' . (int) $_POST['fields']['id'] . ' LIMIT 1');
                if (Tools::set($_POST['fields-null']['path'])) {
                    $this->dbms->query('UNLOCK TABLES');
                    return false;
                }
                $_POST['original']['path'] = null;
                $update = $this->dbms->fetchAndReindex('SELECT id, CONCAT(LEFT(path, '
                    . ($length[1] - PATH_MODULE) . '),
                        LPAD(MID(path, ' . ($length[1] - PATH_MODULE + 1) . ', ' . PATH_MODULE . ') - 1, '
                    . PATH_MODULE . ', "0"),
                        MID(path, ' . ($length[1] + PATH_MODULE + 1) . '))
                    FROM ' . Tools::escapeDbIdentifier($_POST['table']) . '
                    WHERE LEFT(path, ' . ($length[1] - PATH_MODULE) . ') = "'
                    . $this->dbms->escapeSQL(substr($_POST['path-original'], 0, -PATH_MODULE)) . '"
                    AND LENGTH(path) >= ' . $length[1] . ' AND path > "'
                    . $this->dbms->escapeSQL($_POST['path-original']) . '"');
                if ($update) {
                    $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                        . ' SET path = NULL WHERE id IN (' . implode(', ', array_keys($update)) . ')');
                    foreach ($update as $key => $value) {
                        Assert::string($value, 'path must be string');
                        $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                            . ' SET path = "' . $this->dbms->escapeSQL($value) . '" WHERE id = ' . (int) $key);
                    }
                }
            }
            // get path of the "last" child of given parent category, add +1
            $tmp = $this->dbms->fetchSingle('SELECT MAX(MID(path, ' . ($length[0] + PATH_MODULE) . ')) FROM '
                . Tools::escapeDbIdentifier($_POST['table'])
                . ' WHERE LEFT(path, ' . $length[0] . ')="' . $this->dbms->escapeSQL($_POST['path-parent'])
                . '" AND LENGTH(path)=' . ($length[0] + PATH_MODULE));
            $_POST['fields']['path'] = $_POST['path-parent']
                . str_pad((string) (intval($tmp) + 1), PATH_MODULE, '0', STR_PAD_LEFT);
            $this->dbms->query('UNLOCK TABLES');
            return false;
        }
        // todo prozkoumat, co to udělá, když to dojde až sem, zda return false je správná odpověď
        return false;
    }

    /**
     * Custom deletion of a record
     * TODO: the code in this method is based on A project, adapt to dist
     *
     * @return bool - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customDelete()
    {
        if (!isset($_POST['record-delete']) || !$this->authorized()) {
            return false;
        }
        if (!$this->recordDelete()) {
            return true;
        }
        // After a category is deleted, shift categories after it accordingly.
        if ($_POST['table'] == TAB_PREFIX . 'category' && isset($_POST['path-original']) && $_POST['path-original']) {
            $length = strlen($_POST['path-original']);
            $update = $this->dbms->fetchAndReindex('SELECT id, CONCAT(LEFT(path, ' . ($length - PATH_MODULE) . '),
                    LPAD(MID(path, ' . ($length - PATH_MODULE + 1) . ', ' . PATH_MODULE . ') - 1, '
                . PATH_MODULE . ', "0"),
                    MID(path, ' . ($length + PATH_MODULE + 1) . '))
                FROM ' . Tools::escapeDbIdentifier($_POST['table']) . '
                WHERE LEFT(path, ' . ($length - PATH_MODULE) . ') = "'
                . $this->dbms->escapeSQL(substr($_POST['path-original'], 0, -PATH_MODULE)) . '"
                AND LENGTH(path) >= ' . $length . ' AND path > "'
                . $this->dbms->escapeSQL($_POST['path-original']) . '"');
            if ($update) {
                $this->dbms->query('LOCK TABLES ' . Tools::escapeDbIdentifier($_POST['table']) . ' WRITE');
                $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                    . ' SET path = NULL WHERE id IN (' . implode(', ', array_keys($update)) . ')');
                foreach ($update as $key => $value) {
                    Assert::string($value);
                    $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                        . ' SET path = "' . $this->dbms->escapeSQL($value) . '" WHERE id = ' . (int) $key);
                }
                $this->dbms->query('UNLOCK TABLES');
            }
            return true;
        }
        // TODO prozkoumat, co to udělá, když to dojde až sem, zda return false je správná odpověď
        return false;
    }

    /**
     * Custom search. Called to optionally fill the search select
     *
     * @return void
     */
    public function customSearch()
    {
        // no action
    }

    /**
     * Custom condition for filtering.
     * Called to optionally fill conditions to WHERE clause of the SQL statement selecting given table
     *
     * @return void
     */
    public function customCondition()
    {
        // no action
    }

    /**
     * User-defined manipulating with column value of given table
     *
     * @param string $column
     * @param array<mixed> $row
     * @return mixed
     */
    public function customValue($column, array $row)
    {
        return $row[$column];
    }
}
