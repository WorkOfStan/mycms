<?php
namespace GodsDev\MyCMS;

use GodsDev\Tools\Tools;
use Tracy\Debugger;

/**
 * Parent for deployed Admin instance
 *
 */
class MyAdmin extends MyCommon
{

    /** @var MyTableAdmin */
    protected $TableAdmin;

    /** @var array client-side resources - css, js, fonts etc. */
    protected $clientSideResources = [
        'js' => [
            'scripts/jquery.js',
            'scripts/popper.js',
            'scripts/bootstrap.js',
            'scripts/admin.js?v=' . PAGE_RESOURCE_VERSION,
        ],
        'css-pre-admin' => [
            'styles/bootstrap.css',
            ],
        'css' => [
            'styles/font-awesome.css',
            'styles/ie10-viewport-bug-workaround.css',
            'styles/bootstrap-datetimepicker.css',
            'styles/summernote.css',
            'styles/admin.css?v=' . PAGE_RESOURCE_VERSION,
        ]
    ];

    /** @var array tables and columns to search in admin */
    protected $searchColumns = array();

    /**
     *
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options that overrides default values within constructor
     */
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        parent::__construct($MyCMS, $options);
    }

    /**
     * Ends Admin rendering with TracyPanels
     */
    public function endAdmin()
    {
        if (isset($_SESSION['user'])) {
            Debugger::getBar()->addPanel(new \GodsDev\MyCMS\Tracy\BarPanelTemplate('User: ' . $_SESSION['user'], $_SESSION));
        }
        $sqlStatementsArray = $this->MyCMS->dbms->getStatementsArray();
        if (!empty($sqlStatementsArray)) {
            Debugger::getBar()->addPanel(new \GodsDev\MyCMS\Tracy\BarPanelTemplate('SQL: ' . count($sqlStatementsArray), $sqlStatementsArray));
        }
    }

    /**
     * As vendor folder has usually denied access from browser,
     * the content of the standard admin.css MUST be available through this method
     *
     * @return string
     */
    public function getAdminCss()
    {
        return file_get_contents(__DIR__ . '/../styles/admin.css') . PHP_EOL;
    }

    /**
     * Output (in HTML) the <head> section of admin
     *
     * @param string $title used in <title>
     * @result string
     */
    protected function outputHead($title)
    {
        return '<head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
            <meta name="description" content="">
            <meta name="author" content="">
            <title>' . Tools::h(Tools::wrap($title, '', ' - CMS Admin', 'CMS Admin')) . '</title>' . PHP_EOL
            . Tools::arrayListed(Tools::set($this->clientSideResources['css-pre-admin'], []), 0, '', '<link rel="stylesheet" href="', '" />' . PHP_EOL)
            . ' <style type="text/css">' . PHP_EOL
            . $this->getAdminCss() //@todo how to make a link rel instead of inline css?
            . '</style>'. PHP_EOL
            . Tools::arrayListed(Tools::set($this->clientSideResources['css'], []), 0, '', '<link rel="stylesheet" href="', '" />' . PHP_EOL)
            . '<!--[if lt IE 9]>
            <script type="text/javascript" src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script type="text/javascript" src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->'
            . '</head>';
    }

    /**
     * Output (in HTML) the navigation section of admin
     *
     * @result string
     */
    protected function outputNavigation()
    {
        $TableAdmin = $this->TableAdmin;
        $result = '<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
                <a class="nav-item mr-2" href="' . Tools::h($_SERVER['SCRIPT_NAME']) . '">MyCMS</a>
                <button class="btn btn-secondary btn-sm" title="' . $TableAdmin->translate('Search') . '" type="submit" id="nav-search-button"><i class="fa fa-search"></i></button>
                <button class="navbar-toggler d-lg-none" type="button" data-toggle="collapse" aria-expanded="false" data-target="#navbar-content" aria-controls="navbar-content"><span class="navbar-toggler-icon mr-1"></span></button>
                <div class="collapse navbar-collapse" id="navbar-content">
                    <ul class="navbar-nav mr-auto">';
        if (Tools::nonempty($_SESSION['user'])) {
            $result .= $this->outputSpecialMenuLinks()
                . '<li class="nav-item' . (isset($_GET['media']) ? ' active' : '') . '"><a href="?media" class="nav-link"><i class="fa fa-video"></i> ' . $TableAdmin->translate('Media') . '</a></li>
                <li class="nav-item dropdown' . (isset($_GET['user']) ? ' active' : '') . '">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="' . $TableAdmin->translate('User') . '"><i class="fa fa-user"></i> ' . $TableAdmin->translate('User') . '</a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <a href="" class="dropdown-item disabled"><i class="fa fa-user"></i> ' . Tools::h($_SESSION['user']) . '</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item' . (isset($_GET['logout']) ? ' active' : '') . '" href="?user&amp;logout"><i class="fa fa-sign-out-alt mr-1"></i> ' . $TableAdmin->translate('Logout') . '</a>
                  <a class="dropdown-item' . (isset($_GET['change-password']) ? ' active' : '') . '" href="?user&amp;change-password"><i class="fa fa-id-card mr-1"></i> ' . $TableAdmin->translate('Change password') . '</a>
                  <a class="dropdown-item' . (isset($_GET['create-user']) ? ' active' : '') . '" href="?user&amp;create-user"><i class="fa fa-user-plus mr-1"></i> ' . $TableAdmin->translate('Create user') . '</a>
                  <a class="dropdown-item' . (isset($_GET['delete-user']) ? ' active' : '') . '" href="?user&amp;delete-user"><i class="fa fa-user-times mr-1"></i> ' . $TableAdmin->translate('Delete user') . '</a>
                </div>
              </li>';
        }
        $result .= '<li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="' . $TableAdmin->translate('Settings') . '"><i class="fa fa-cog"></i> ' . $TableAdmin->translate('Settings') . '</a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdown">';
        foreach ($TableAdmin->TRANSLATIONS as $key => $value) {
            $result .= '<a class="dropdown-item' . ($key == $_SESSION['language'] ? ' active' : '') . '" href="?' . Tools::urlChange(array('language' => $key)) . '"><i class="fa fa-flag mr-1"></i> ' . Tools::h($value) . '</a>' . PHP_EOL;
        }
        if (isset($_SESSION['user'])) {
            $result .= '<div class="dropdown-divider"></div><a class="dropdown-item" href="" id="toggle-nav" title="' . Tools::h($TableAdmin->translate('Toggle sidebar')) . '"><i class="fa fa-columns mr-1"></i> ' . $TableAdmin->translate('Sidebar') . '</a>'
                . $this->outputSpecialSettingsLinks();
        }
        $result .= '</div></li></ul></div>';
        if (isset($_SESSION['user'])) {
            $result .= '<form class="collapse mt-md-0" id="nav-search-form">'
                . Tools::htmlInput('search', '', Tools::set($_GET['search'], ''), array('class' => 'form-control', 'placeholder' => $TableAdmin->translate('Search'), 'required' => true, 'id' => 'nav-search-input'))
                . '</form>';
        }
        $result .= '
        </nav>';
        return $result;
    }

    /**
     * Output (in HTML) the project-specific links in the navigation section of admin
     *
     * @result string
     */
    protected function outputSpecialMenuLinks()
    {
        return '';
    }

    /**
     * Output (in HTML) the project-specific links in the settings section of admin
     *
     * @result string
     */
    protected function outputSpecialSettingsLinks()
    {
        return '';
    }

    /**
     * Output (in HTML) the media section of admin
     *
     * @result string
     */
    protected function outputMedia()
    {
        $TableAdmin = $this->TableAdmin;
        $result = '<h1 class="page-header">' . $TableAdmin->translate('Media') . '</h1>
            <form action="" method="post" enctype="multipart/form-data">
                <fieldset>
                    <legend>' . $TableAdmin->translate('Upload') . '</legend>
                    <label for="subfolder">' . $TableAdmin->translate('Folder') . ':</label>
                    <select name="subfolder" id="subfolder" class="form-control">'
                    . Tools::htmlOption('', DIR_ASSETS);
        Tools::setifnull($_SESSION['assetsSubfolder'], '');
        if (!is_dir(DIR_ASSETS . $_SESSION['assetsSubfolder'])) {
            $_SESSION['assetsSubfolder'] = '';
        }
        foreach ($this->ASSETS_SUBFOLDERS as $value) {
            $result .= Tools::htmlOption($value, DIR_ASSETS . $value, $_SESSION['assetsSubfolder']);
        }
        $result .= '</select>
                    <label>' . $TableAdmin->translate('Files') . ':</label>
                    <div id="files-div">
                        <input type="file" name="files[]" class="form-control" onchange="if($(this).val()){$(\'#files-div\').append($(this).prop(\'outerHTML\'));}" />
                    </div>
                    ' . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden') . '
                    <button type="submit" name="upload-media" value="1" class="btn btb-lg btn-primary"><i class="fa fa-upload"></i> ' . $TableAdmin->translate('Upload') . '</button>
                </fieldset>
            </form><hr />
            <details class="uploaded-files" open><summary>' . $TableAdmin->translate('Uploaded files') . ' <small class="badge badge-secondary"></small></summary>
            <div id="media-files"></div>
            <div id="file-ops">
                <div id="media-feedback" class="alert alert-warning alert-dismissible" style="display:none;"></div>
                <button class="btn btn-secondary mr-2 disabled" title="' . $TableAdmin->translate('Delete') . '" id="delete-media-files"><i class="fa fa-check-square"></i> <i class="fa fa-trash"></i></button>
                <fieldset class="d-inline-block position-relative" id="filename-fieldset">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <button class="btn btn-secondary" type="button" readonly title="' . $TableAdmin->translate('File') . '"><i class="fa fa-file"></i></button>
                        </div>' 
                        . Tools::htmlInput('', '', '', array('class' => 'form-control form-control-sm', 'id' => 'media-file-name')) . '
                    </div>
                </fieldset>
                <fieldset class="d-inline-block position-relative mr-2" id="filename-fieldset">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <button class="btn btn-secondary" type="button" title="' . $TableAdmin->translate('Folder') . '"><i class="fa fa-folder"></i></button>
                        </div>
                        <select id="file-rename-folder" name="file-rename-folder" class="form-control form-control-sm form-control-inline d-inline-block w-initial"></select>
                    </div>
                </fieldset>
                <button class="btn btn-secondary disabled" type="submit" title="' . $TableAdmin->translate('Rename') . '" id="rename-media-file"><i class="fa fa-dot-circle"></i> <i class="fa fa-i-cursor"></i></button>
                <button class="btn btn-secondary disabled" type="submit" title="' . $TableAdmin->translate('Pack') . '" id="pack-media-files"><i class="fa fa-check-square"></i> <i class="fa fa-caret-right"></i> <i class="fa fa-file-archive"></i></button>
                <button class="btn btn-secondary disabled" type="submit" title="' . $TableAdmin->translate('Unpack') . '" id="unpack-media-file"><i class="fa fa-dot-circle"></i> <i class="far fa-file-archive"></i> <i class="fa fa-caret-right"></i></button>
            </div>
            </details>';
        return $result;
    }

    /**
     * Output (in HTML) the user section of admin
     *
     * @result string
     */
    protected function outputUser()
    {
        $TableAdmin = $this->TableAdmin;
        $result = '<h1>' . $TableAdmin->translate('User') . '</h1>';
        // logout
        if (isset($_GET['logout'])) {
            $result .= '<h2><small>' . $TableAdmin->translate('Logout') . '</small></h2>
                <form action="" method="post" id="logout-form" class="panel d-inline-block"><fieldset class="card p-2">
                <button type="submit" name="logout" class="form-control btn-primary text-left"><i class="fa fas fa-sign-out fa-sign-out-alt"></i> ' . $TableAdmin->translate('Logout') . '</button>'
                . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden') . '
                </fieldset></form>';
        }
        // change password
        if (isset($_GET['change-password'])) {
            $result .= '<h2><small>' . $TableAdmin->translate('Change password') . '</small></h2>
                <form action="" method="post" id="change-password-form"><fieldset class="card p-2">';
            $options = array(
                'type' => 'password',
                'before' => '<div class="col-sm-3">',
                'between' => '</div><div class="col-sm-9">',
                'after' => '</div>',
                'class' => 'form-control'
            );
            $result .= Tools::htmlInput('old-password', $TableAdmin->translate('Old password', false) . ':', '', $options + array('id' => 'old-password', 'autocomplete' => 'off'))
                . Tools::htmlInput('new-password', $TableAdmin->translate('New password', false) . ':', '', $options + array('id' => 'new-password', 'autocomplete' => 'new-password'))
                . Tools::htmlInput('retype-password', $TableAdmin->translate('Retype password', false) . ':', '', $options + array('id' => 'retype-password', 'autocomplete' => 'new-password'))
                . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden')
                . '<button type="submit" name="change-password" class="btn btn-primary my-3 ml-3"><i class="fas fa-id-card mr-1"></i> ' . $TableAdmin->translate('Change password') . '</button>
                </fieldset></form>';
        }
        // create user
        if (isset($_GET['create-user'])) {
            $result .= '<h2><small>' . $TableAdmin->translate('Create user') . '</small></h2>
                <form action="" method="post" class="panel create-user-form"><fieldset class="card p-2">'
                . Tools::htmlInput('user', $TableAdmin->translate('User', false) . ':', '', array('class' => 'form-control', 'id' => 'create-user'))
                . Tools::htmlInput('password', $TableAdmin->translate('Password', false) . ':', '', array('type' => 'password', 'class' => 'form-control', 'id' => 'create-password'))
                . Tools::htmlInput('retype-password', $TableAdmin->translate('Retype password', false) . ':', '', array('type' => 'password', 'class' => 'form-control', 'id' => 'create-retype-password'))
                . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden') . '
                  <button type="submit" name="create-user" class="btn btn-primary my-2"><i class="fa fa-user-plus"></i> ' . $TableAdmin->translate('Create user') . '</button>
                </fieldset></form>';
        }
        // delete user
        if (isset($_GET['delete-user'])) {
            $result .= '<h2><small>' . $TableAdmin->translate('Delete user') . '</small></h2>';
            if ($users = $this->MyCMS->fetchAll('SELECT id,admin,active FROM ' . TAB_PREFIX . 'admin')) {
                $result .= '<ul class="list-group list-group-flush">';
                foreach ($users as $user) {
                    $result .= '<li class="list-group-item">
                        <form action="" method="post" class="form-inline d-inline-block delete-user-form' . ($user['active'] == 1 ? '' : ' inactive-item') . '" onsubmit="return confirm(\'' . $TableAdmin->translate('Really delete?') . '\')">'
                            . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden')
                            . '<button type="submit" name="delete-user" value="' . Tools::h($user['admin']) . '"' . ($user['admin'] == $_SESSION['user'] ? ' disabled' : '') . ' class="btn btn-primary" title="' . $TableAdmin->translate('Delete user') . '?">'
                            . '<i class="fa fa-user-times"></i></button> '
                            . Tools::htmlInput('', '', $user['id'], array('type' => 'checkbox', 'checked' => ($user['active'] ? 1 : null), 'class' => 'user-activate', 'title' => $TableAdmin->translate('Activate/deactivate'))) . ' '
                            . '<tt>' . Tools::h($user['admin']) . '</tt>
                        </form>
                        </li>' . PHP_EOL;
                }
                $result .= '</ul>';
            } else {
                $result .= '<p class="alert alert-warning">' . $TableAdmin->translate('No records found.') . '</p>';
            }
        }
        return $result;
    }

    /**
     * Output (in HTML) the login section of admin
     *
     * @result string
     */
    protected function outputLogin()
    {
        $TableAdmin = $this->TableAdmin;
        $options = array(
            'before' => '<div class="col-sm-3">',
            'between' => '</div><div class="col-sm-9">',
            'after' => '</div>',
            'class' => 'form-control'
        );
        return '<h1>' . $TableAdmin->translate('Login') . '</h1>
            <form action="" method="post" class="form" id="login-form">
            <div>'
            . Tools::htmlInput('user', $TableAdmin->translate('User', false) . ':', Tools::setifnull($_SESSION['user']), $options)
            . Tools::htmlInput('password', $TableAdmin->translate('Password', false) . ':', '', array('type' => 'password', 'id' => 'login-password') + $options)
            . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden') . '</div>
            <div class="col-sm-9 col-sm-offset-3 my-3"><button type="submit" name="login" class="btn btn-primary"><i class="fa fas fa-sign-in fa-sign-in-alt"></i> ' . $TableAdmin->translate('Login') . '</button>
            </div>
            </form>';
    }

    /**
     * Output (in HTML) the dashboard section of admin.
     *
     * @result string
     */
    protected function outputDashboard()
    {
        $TableAdmin = $this->TableAdmin;
        $result = '<br class="m-3"/><br class="m-3"/><hr />
            <div><small>' . $TableAdmin->translate('For more detailed browsing with filtering etc. you may select one of the following tables…') . '</small></div>
            <div class="detailed-tables">';
        foreach (array_keys($TableAdmin->tables) as $table) {
            if (substr($table, 0, strlen(TAB_PREFIX)) != TAB_PREFIX) {
                continue;
            }
            $result .= '<a href="?table=' . urlencode($table) . '&amp;where[id]="><i class="far fa-plus-square" title="' . $TableAdmin->translate('New record') . '"></i></a> '
                . '<a href="?table=' . urlencode($table) . '" class="d-inline' . ($_GET['table'] == $table ? ' active' : '') . '">'
                . '<i class="fa fa-table"></i> '
                . Tools::h(substr($table, strlen(TAB_PREFIX)))
                . ($table == $_GET['table'] ? ' <span class="sr-only">(' . $TableAdmin->translate('current') . ')</span>' : '')
                . '</a> &nbsp; ' . PHP_EOL;
        }
        $result .= '</div>';
        return $result;
    }

    /**
     * Output (in HTML) the agendas section of admin.
     * This method also modifies $this->script.
     *
     * @result string
     */
    protected function outputAgendas()
    {
        $TableAdmin = $this->TableAdmin;
        // show agendas in the sidebar
        $result = '<details id="agendas"><summary class="page-header">' . $TableAdmin->translate('Agendas') . '<br />'
            . $TableAdmin->translate('Select your agenda, then particular row.') . '</summary><div class="ml-3">' . PHP_EOL;
        foreach ($this->agendas as $agenda => $option) {
            Tools::setifempty($option['table'], $agenda);
            $result .= '<details class="my-1" id="details-' . $agenda . '">
                <summary><i class="fa fa-table"></i> ' . Tools::h(Tools::setifempty($option['display'], $agenda)) . '</summary>
                <div class="card" id="agenda-' . $agenda . '"></div>
                </details>' . PHP_EOL;
            if (isset($option['prefill'])) {
                $tmpBadge = ',prefill:{';
                foreach ($option['prefill'] as $key => $value) {
                    $tmpBadge .= json_encode($key) . ':' . json_encode($value) . ',';
                }
                $tmpBadge = substr($tmpBadge, 0, -1) . '}';
            } else {
                $tmpBadge = '';
            }
            $TableAdmin->script .= 'getAgenda(' . json_encode($agenda) . ',{table:' . json_encode($option['table'] ?: $agenda) . $tmpBadge . '});' . PHP_EOL;
        }
        $result .= '</div></details>';
        $TableAdmin->script .= '$("#agendas > summary").click();';
        return $result;
    }

    /**
     * Output (in HTML) the end part of administration page.
     * This method also modifies $this->script.
     *
     * @result string
     */
    protected function outputBodyEnd()
    {
        $TableAdmin = $this->TableAdmin;
        $result =
            Tools::arrayListed(Tools::set($this->clientSideResources['js'], []), 0, '', '<script type="text/javascript" src="', '"></script>')
//            . (empty($this->javascripts) ? '' : ('<script type="text/javascript" src="' . implode('"></script><script type="text/javascript" src="', $this->javascripts) . '"></script>' ))
            //<script type="text/javascript" src="scripts/bootstrap-datetimepicker.js"></script>
            . '<script type="text/javascript" src="scripts/jquery.sha1.js"></script>'
            . '<script type="text/javascript" src="scripts/summernote.js"></script>'
//            . '<script type="text/javascript" src="scripts/admin.js?v=' . PAGE_RESOURCE_VERSION . '" charset="utf-8"></script>'
            . '<script type="text/javascript" src="scripts/admin-specific.js?v=' . PAGE_RESOURCE_VERSION . '" charset="utf-8"></script>'
            . '<script type="text/javascript">' . PHP_EOL;
        $tmp = array_flip(explode('|', 'Descending|Really delete?|Really?|New record|Passwords don\'t match!|Please, fill necessary data.|close|'
            . 'Select at least one file and try again.|Select at least one record and try again.|No files|Edit|variable|value|name|size|modified|'
            . 'Select|No records found.|Please, choose a new name.|Please, fill up a valid file name.'));
        foreach ($tmp as $key => $value) {
            $tmp[$key] = $TableAdmin->translate($key, false);
        }
        $result .= 'WHERE_OPS = ' . json_encode($TableAdmin->WHERE_OPS) . ';' . PHP_EOL
            . 'TRANSLATE = ' . json_encode($tmp) . ';' . PHP_EOL
            . 'TAB_PREFIX = "' . TAB_PREFIX . '";' . PHP_EOL
            . 'EXPAND_INFIX = "' . EXPAND_INFIX . '";' . PHP_EOL
            . 'TOKEN = ' . end($_SESSION['token']) . ';' . PHP_EOL
            . 'ASSETS_SUBFOLDERS = ' . json_encode($this->ASSETS_SUBFOLDERS, true) . ';' . PHP_EOL
            . 'DIR_ASSETS = ' . json_encode(DIR_ASSETS, true) . ';' . PHP_EOL
            . '$(document).ready(function(){' . PHP_EOL
            . $TableAdmin->script . PHP_EOL
            . 'if (typeof(AdminRecordName) != "undefined") {' . PHP_EOL
            . '    $("h2 .AdminRecordName").text(AdminRecordName.replaceAll(/<\/?[a-z][^>]*>/i, "").substr(0, 50));' . PHP_EOL
            . '}' . PHP_EOL
            . '});' . PHP_EOL
            .' </script>';
        return $result;
    }

    /**
     * Output (in HTML) the Bootstrap dialog for ImageSelector
     *
     * @result string
     */
    protected function outputImageSelector()
    {
        $TableAdmin = $this->TableAdmin;
        return '<div class="modal" id="image-selector" tabindex="-1" role="dialog" data-type="modal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">' . $TableAdmin->translate('Reload') . '</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="' . $TableAdmin->translate('close') . '"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <select name="subfolder" id="modalImageFolder" class="form-control form-control-sm" onchange="updateImageSelector($(this), $(this).parent().find(\'.ImageFiles\'))">
                        </select>
                        <div id="modalImageFiles" class="ImageFiles"></div>
                        <label class="note-form-label">' . $TableAdmin->translate('Image URL') . ':</label><br />
                        <input class="note-image-url form-control form-control-sm" type="text" id="modalImagePath" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary note-image-url pull-left" id="modalReloadImages"><i class="fa fa-refresh"></i> ' . $TableAdmin->translate('Reload') . '</button>
                        <button type="button" class="btn btn-primary note-image-url" id="modalInsertImage"><i class="fa fa-image"></i> ' . $TableAdmin->translate('Insert') . '</button>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Output (in HTML) the listing or editing section of a table (selected in $_GET['table'])
     *
     * @result string
     */
    protected function outputTable()
    {
        $TableAdmin = $this->TableAdmin;
        $tablePrefixless = mb_substr($_GET['table'], mb_strlen(TAB_PREFIX));
        $result = '<h1 class="page-header">'
            . '<a href="?table=' . Tools::h($_GET['table']) . '" title="' . $TableAdmin->translate('Back to listing') . '"><i class="fa fa-list-alt"></i></a> '
            . '<tt>' . Tools::h($tablePrefixless) . '</tt></h1>' . PHP_EOL;
        if (isset($_GET['where']) && is_array($_GET['where'])) {
            // table edit
            $result .= '<h2 class="sub-header">' . $TableAdmin->translate('Edit') . ' <span class="AdminRecordName"></span></h2>';
            $tabs = array(null);
            foreach (Tools::set($this->TableAdmin->tableContext['language-versions'], $this->MyCMS->TRANSLATIONS) as $key => $value) {
                $tabs[$value] = "~^.+_$key$~i";
            }
            $result .= $this->outputTableBeforeEdit()
                . $TableAdmin->outputForm($_GET['where'], array(
                    'layout-row' => true,
                    'prefill' => isset($_GET['prefill']) && is_array($_GET['prefill']) ? $_GET['prefill'] : array(),
                    'original' => true,
                    'tabs' => $tabs,
                    'return-output' => 1
                ))
                . $this->outputTableAfterEdit();
        } elseif (isset($_POST['edit-selected'])) {
              $result .= $this->outputTableEditSelected();
        } else {
            // table listing
            $result .= '<h2 class="sub-header">' . $TableAdmin->translate('Listing') . '</h2>'
                . $this->outputTableBeforeListing()
                . $TableAdmin->view(array('return-output'=>1))
                . $this->outputTableAfterListing();
        }
        return $result;
    }

    /**
     * Output (in HTML) the admin's layout footer
     *
     * @result string
     */
    protected function outputFooter()
    {
        return '<footer class="sticky-footer">&copy; GODS, s r.o. ' . $this->TableAdmin->translate('All rights reserved.') . '</footer>';
    }

    /**
     * Return if a project-specific sections should be displayed in admin.
     *
     * @return bool
     */
    protected function projectSpecificSectionsCondition()
    {
        return false;
    }

    /**
     * Output (in HTML) the project-specific sections
     *
     * @result string
     */
    protected function projectSpecificSections()
    {
        return '';
    }

    /**
     * Output (in HTML) project-specific code before listing of selected table
     *
     * @result string
     */
    protected function outputTableBeforeListing()
    {
        return '';
    }

    /**
     * Output (in HTML) project-specific code after listing of selected table
     *
     * @result string
     */
    protected function outputTableAfterListing()
    {
        return '';
    }

    /**
     * Output (in HTML) project-specific code before editing a record from selected table
     *
     * @result string
     */
    protected function outputTableBeforeEdit()
    {
        return '';
    }

    /**
     * Output (in HTML) project-specific code after editing a record from selected table
     *
     * @result string
     */
    protected function outputTableAfterEdit()
    {
        return '';
    }

    /**
     * Output (in HTML) code after editing a record from selected table
     * Default behaviour is a standard LIKE%% search in tables and columns
     * defined in $this->searchColumns.
     *
     * @param string $keyword
     * @result string
     */
    protected function outputSearchResults($keyword)
    {
        $keyword2 = $this->TableAdmin->escapeSQL($keyword);
        $result = '';
        foreach ($this->searchColumns as $key => $value) {
            $id = array_shift($value);
            $sql = 'SELECT ' . $this->TableAdmin->escapeDbIdentifier($id) . ','
                . $this->TableAdmin->escapeDbIdentifier(strtr(reset($value), ['_#' => '_' . $_SESSION['language']])) 
                . ' FROM ' . $this->TableAdmin->escapeDbIdentifier(TAB_PREFIX . $key);
            $where = '';
            foreach ($value as $item) {
                $where .= ' OR LCASE(' . $this->TableAdmin->escapeDbIdentifier(strtr($item, ['_#' => '_' . $_SESSION['language']]))
                    . ') LIKE LCASE("%' . $keyword2 . '%")';
            }
            $sql .= ' WHERE ' . substr($where, 4) . ' LIMIT 100';
            if ($rows = $this->MyCMS->fetchAll($sql)) {
                $result .= '<h3><a href="?table=' . urlencode(TAB_PREFIX . $key) . '"><i class="fa fa-table"></i></a> <tt>' . Tools::h($key) . '</tt></h3>' . PHP_EOL . '<ul>';
                foreach ($rows as $row) {
                    $row = array_values($row);
                    $result .= '<li><a href="?table=' . urlencode(TAB_PREFIX . $key) . '&amp;where[' . urlencode($id) . ']=' . urlencode($row[0]) . '">'
                        . Tools::h($row[1]) . '</a>';
                    $where = '';
                    for ($i = 1; $i < count($row); $i++) {
                        $row[$i] = strip_tags($row[$i]);
                        if (($p = stripos($row[$i], $keyword)) !== false) {
                            $row[$i] = preg_replace('~(' . preg_quote($keyword) . ')~six', '<b>${1}</b>', $row[$i]);
                            $where .= '<li>…' . mb_substr($row[$i], max($p - 50, 0), strlen($keyword) + 100) . '…</li>' . PHP_EOL;
                        }
                    }
                    $result .= Tools::wrap($where, '<ul>', '</ul>') . '</li>' . PHP_EOL;
                }
                $result .= '</ul>';
            }
        }
        return '<h2>' . $this->TableAdmin->translate('Search results') . '</h2>' . PHP_EOL
            . '<div>' . $this->TableAdmin->translate('Search') . ': <tt>' . Tools::h($keyword) . '</tt></div>' . PHP_EOL
            . $result ?: $this->TableAdmin->translate('No records found.');
    }

    /**
     * Output (in HTML) a form to edit multiple selected rows of a table
     *
     * @result string
     */
    protected function outputTableEditSelected()
    {
        Tools::setifnull($_POST['check'], array());
        $result = '<form action="" method="post" enctype="multipart/form-data" class="selected-records-form">'
            . '<p class="lead">' . $this->TableAdmin->translate('Edit selected') . ' (' . (isset($_POST['total-rows']) ? $_POST['total-rows'] : count($_POST['check'])) . ')</p>' 
            . Tools::htmlInput('database-table', '' , $_POST['database-table'], 'hidden')
            . Tools::htmlInput('token', '' , $_POST['token'], 'hidden')
            . Tools::htmlInput('total-rows', '' , Tools::ifset($_POST['total-rows']), 'hidden')
            . '<table class="table table-striped table-edit-selected">';
        foreach ($this->TableAdmin->fields as $key => $value) {
            $result .= '<tr><th>' . $this->TableAdmin->translateColumn($key) . '</th>' . PHP_EOL
                . '<td class="w-initial">';
            $op = array('original' => $this->TableAdmin->translate('original'), 
                        'value' => '=' //$this->TableAdmin->translate('value')
                ) + ($value['null'] ? array('null' => 'NULL') : array());
            $opOptions = array(
                'class' => 'form-control w-initial p-1', 
                'data-type' => $value['type'], 
                'data-for' => $key
            );
            switch ($value['basictype']) {
                case 'integer': case 'rational':
                    $result .= Tools::htmlSelect("op[$key]", 
                        $op
                        + array('+' => '+', '-' => '-') 
                        + ($value['basictype'] == 'rational' ? array('*' => '*') : array())
                        + array('random' => 'random'),
                        '', $opOptions
                    ) . '</td><td>' . Tools::htmlInput("fields[$key]", '', '', array('type' => 'number', 'class' => 'form-control edit-selected text-right w-initial', 'data-size' => $value['size'])) . '</td>';
                    break;
                case 'text': case 'binary':
                    if (Tools::among($value['type'], 'date', 'datetime', 'time', 'timestamp')) {
                        $result .= Tools::htmlSelect("op[$key]", 
                            $op
                            + array('now' => 'now')
                            + (Tools::among($value['type'], 'date', 'datetime') ? array('+interval' => '+interval', '-interval' => '-interval') : array()) 
                            + (Tools::among($value['type'], 'datetime', 'time', 'timestamp') ? array('addtime' => 'addtime', 'subtime' => 'subtime') : array()),
                            '', $opOptions
                        );
                    } else {
                        $result .= Tools::htmlSelect("op[$key]", 
                            $op + array(
                                'prepend' => $this->TableAdmin->translate('prepend'), 
                                'append' => $this->TableAdmin->translate('append'), 
                                'md5' => 'md5', 
                                'sha1' => 'sha1', 
                                'password' => 'password', 
                                'uuid' => 'uuid'
                            ), '', $opOptions
                        );
                    }
                    $result .= '</td><td>';
                    if (Tools::ends($value['type'], 'text') || Tools::ends($value['type'], 'blob')) {
                        $result .= Tools::htmlTextarea("fields[$key]", '', null, 3, array('class' => 'form-control edit-selected', 'data-size' => $value['size'])) . '</td>';
                    } else {
                        $result .= Tools::htmlInput("fields[$key]", '', '', array('type' => 'text', 'class' => 'form-control edit-selected', 'data-size' => $value['size'])) . '</td>';
                    }
                    break;
                case 'choice':
                    $matches = $this->TableAdmin->decodeChoiceOptions($this->TableAdmin->fields[$key]['size']);
                    if ($value['type'] == 'set') {
                        $result .= Tools::htmlSelect("op[$key]", 
                            $op + array(
                                'add' => $this->TableAdmin->translate('add options'), 
                                'remove' => $this->TableAdmin->translate('remove options'), 
                            ), '', $opOptions
                        );
                        $result .= '</td><td>' . Tools::htmlInput("fields[$key]", '', 1, 'hidden');
                        foreach ($matches as $matchKey => $match) {
                            $result .= Tools::htmlInput("fields[$key][]", ($tmp = strtr($match, array("''" => "'", "\\\\" => "\\"))) === '' ? '<i>' . $this->TableAdmin->translate('nothing') . '</i>' : $tmp, 1 << $matchKey, 
                                array('type' => 'checkbox', 'label-after' => true, 'class' => 'edit-selected', 'label-class' => 'ml-1 mr-2', 'random-id' => true, 'label-html' => $tmp === ''));
                        }
                    } else { //enum
                        $result .= Tools::htmlSelect("op[$key]", $op, '', $opOptions) 
                            . '</td><td>' 
                            . '<i>' . Tools::htmlInput("fields[$key]", $this->TableAdmin->translate('empty'), 0, array('type' => 'radio', 'class' => 'edit-selected', 'label-after' => true, 'label-class' => 'mr-2', 'random-id' => true)) . '</i>';
                        foreach ($matches as $matchKey => $match) {
                            $result .= Tools::htmlInput("fields[$key]", ($tmp = strtr($match, array("''" => "'", "\\\\" => "\\"))) === '' ? '<i>' . $this->TableAdmin->translate('nothing') . '</i>' : $tmp, $matchKey + 1, 
                                array('type' => 'radio', 'label-after' => true, 'class' => 'edit-selected', 'label-class' => 'ml-1 mr-2', 'random-id' => true, 'label-html' => $tmp === ''));
                        }
                    }
                    $result .= '</td>' . PHP_EOL;
                    break;
            }
            $result .= "\n</tr>\n";
        }
        $result .= '</table><div>
            <button name="save-selected" class="btn btn-primary mr-1" value="1"><i class="fa fa-save mr-1"></i> ' . $this->TableAdmin->translate('Save') . '</button>
            <button name="delete-selected" class="btn btn-secondary" value="1"><i class="fa fa-trash mr-1"></i> ' . $this->TableAdmin->translate('Delete') . '</button>';
        if (isset($_POST['check-all'])) {
            $result .= Tools::htmlInput('check-all', '', 1, 'hidden') . PHP_EOL;
        } elseif (Tools::setarray($_POST['check'])) {
            foreach ($_POST['check'] as $value) {
                $result .= Tools::htmlInput('check[]', '', $value, 'hidden') . PHP_EOL;
            }
        }
        $result .= '</div></form>';
        return $result;
    }

    /**
     * Return the HTML output of all administration page.
     *
     * Expected global variables:
     * * $_GET
     * * $_SESSION
     * * $_SERVER['SCRIPT_NAME']
     *
     * Expected constants:
     * * DIR_ASSETS
     * * TAB_PREFIX
     * * EXPAND_INFIX
     *
     * @return string
     */
    public function outputAdmin()
    {
        //@todo replace the two local variables by the object wide variables below:
        $MyCMS = $this->MyCMS;
        $TableAdmin = $this->TableAdmin;
        $MyCMS->csrfStart();

        Debugger::barDump($MyCMS, 'MyCMS');
        Debugger::barDump($this->agendas, 'Agendas');
        Debugger::barDump($_SESSION, 'Session');

        //$TableAdmin = new \GodsDev\AltronNet\TableAdmin($MyCMS->dbms, Tools::set($_GET['table']), array('SETTINGS' => $MyCMS->SETTINGS));
        if (!in_array(Tools::set($_GET['table']), array_keys($TableAdmin->tables))) {
            $_GET['table'] = '';
        }
        $TableAdmin->setTable($_GET['table']);
        $tablePrefixless = mb_substr($_GET['table'], mb_strlen(TAB_PREFIX));
        if (!isset($_SESSION['user'])) {
            $_GET['table'] = $_GET['media'] = $_GET['user'] = null;
        }
        $tmpTitle = $tablePrefixless ?: (isset($_GET['user']) ? $TableAdmin->translate('User') : (isset($_GET['media']) ? $TableAdmin->translate('Media') : (isset($_GET['products']) ? $TableAdmin->translate('Products') : (isset($_GET['pages']) ? $TableAdmin->translate('Pages') : ''))));
        $output = '<!DOCTYPE html><html lang="' . Tools::h($_SESSION['language']) . '">'
            . $this->outputHead($this->getPageTitle())
            . '<body>' . PHP_EOL . '<header>'
            . $this->outputNavigation()
            . '</header>' . PHP_EOL . '<div class="container-fluid row">' . PHP_EOL;
        if (isset($_SESSION['user']) && $_SESSION['user']) {
            $output .= '<nav class="col-md-3 bg-light sidebar order-last" id="admin-sidebar">' . $this->outputAgendas() . '</nav>' . PHP_EOL;
        }
        $output .= '<main class="ml-3 ml-sm-auto col-md-9 pt-3" role="main" id="admin-main">'
            . Tools::showMessages(false);
        foreach (glob(DIR_ASSETS . '*', GLOB_ONLYDIR) as $value) {
            $this->ASSETS_SUBFOLDERS []= substr($value, strlen(DIR_ASSETS));
        }
        // search results
        if (isset($_SESSION['user']) && Tools::set($_GET['search'])) {
            $output .= $this->outputSearchResults($_GET['search']);
        }
        // table listing/editing
        if ($_GET['table']) {
            $output .= $this->outputTable();
        }
        // media upload etc.
        elseif (isset($_GET['media'])) {
            $output .= $this->outputMedia();
        }
        // user operations (logout, change password, create user, delete user)
        elseif (isset($_GET['user'])) {
            $output .= $this->outputUser();
        }
        // user not logged in - show a login form
        elseif(!isset($_SESSION['user'])) {
            $output .= $this->outputLogin();
        }
        // project-specific admin sections
        elseif ($this->projectSpecificSectionsCondition()) {
            $output .= $this->projectSpecificSections($TableAdmin);
        } else {
            // no agenda selected, showing "dashboard"
        }
        if (isset($_SESSION['user'])) {
            $output .= $this->outputDashboard();
        }
        $output .= '</main></div>' . PHP_EOL
            . $this->outputFooter();
        if (isset($_SESSION['user'])) {
            $output .= $this->outputImageSelector();
        }
        $output .= $this->outputBodyEnd()
            . '</body>' . PHP_EOL .'</html>';
        return $output;
    }

    public function getPageTitle()
    {
        $tablePrefixless = mb_substr(Tools::set($_GET['table']), mb_strlen(TAB_PREFIX));
        if (!isset($_SESSION['user'])) {
            $_GET['table'] = $_GET['media'] = $_GET['user'] = null;
        }
        return $tablePrefixless ?: 
            (isset($_GET['user']) ? $this->TableAdmin->translate('User') : 
                (isset($_GET['media']) ? $this->TableAdmin->translate('Media') : 
                    (isset($_GET['products']) ? $this->TableAdmin->translate('Products') : 
                        (isset($_GET['pages']) ? $this->TableAdmin->translate('Pages') : 
                            (isset($_GET['search']) ? $this->TableAdmin->translate('Search') : '')
                        )
                    )
                )
            );
    }
}
