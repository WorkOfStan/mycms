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
        ],
        'css' => [
            'styles/font-awesome.css',
            'styles/ie10-viewport-bug-workaround.css',
            'styles/bootstrap-datetimepicker.css',
            'styles/summernote.css',
        ]
    ];
    
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
    protected function outputAdminHead($title)
    {
        return '<head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
            <meta name="description" content="">
            <meta name="author" content="">
            <title>' . Tools::h(Tools::wrap($title, '', ' - CMS Admin', 'CMS Admin')) . '</title>
            <style type="text/css">'
            . $this->getAdminCss() //@todo maybe link rel instead of inline css
            . '</style>
            ' . Tools::arrayListed(Tools::set($this->clientSideResources['css'], []), 0, '', '<link rel="stylesheet" href="', '" />') . '
            <!--[if lt IE 9]>
            <script type="text/javascript" src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script type="text/javascript" src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->'
            . Tools::arrayListed(Tools::set($this->clientSideResources['js'], []), 0, '', '<script type="text/javascript" src="', '"></script>')
            . '</head>';
    }

    /**
     * Output (in HTML) the navigation section of admin
     *
     * @result string
     */
    protected function outputAdminNavigation()
    {
        $TableAdmin = $this->TableAdmin;
        $result = '<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
                <a class="nav-item mr-2" href="' . Tools::h($_SERVER['SCRIPT_NAME']) . '">MyCMS</a>
                <button class="navbar-toggler d-lg-none" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarsExampleDefault">
                    <ul class="navbar-nav mr-auto">';
        if (Tools::nonempty($_SESSION['user'])) {
            $result .= $this->outputAdminProjectSpecificLinks()
                . '<li class="nav-item' . (isset($_GET['media']) ? ' active' : '') . '"><a href="?media" class="nav-link"><i class="fa fa-video-camera" aria-hidden="true"></i> ' . $TableAdmin->translate('Media') . '</a></li>
                <li class="nav-item dropdown' . (isset($_GET['user']) ? ' active' : '') . '">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="' . $TableAdmin->translate('User') . '"><i class="fa fa-user" aria-hidden="true"></i> ' . $TableAdmin->translate('User') . '</a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <a href="" class="dropdown-item disabled"><i class="fa fa-user" aria-hidden="true"></i> ' . Tools::h($_SESSION['user']) . '</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item' . (isset($_GET['logout']) ? ' active' : '') . '" href="?user&amp;logout"><i class="fa fa-sign-out" aria-hidden="true"></i> ' . $TableAdmin->translate('Logout') . '</a>
                  <a class="dropdown-item' . (isset($_GET['change-password']) ? ' active' : '') . '" href="?user&amp;change-password"><i class="fa fa-id-card-o" aria-hidden="true"></i> ' . $TableAdmin->translate('Change password') . '</a>
                  <a class="dropdown-item' . (isset($_GET['create-user']) ? ' active' : '') . '" href="?user&amp;create-user"><i class="fa fa-user-plus" aria-hidden="true"></i> ' . $TableAdmin->translate('Create user') . '</a>
                  <a class="dropdown-item' . (isset($_GET['delete-user']) ? ' active' : '') . '" href="?user&amp;delete-user"><i class="fa fa-user-times" aria-hidden="true"></i> ' . $TableAdmin->translate('Delete user') . '</a>
                </div>
              </li>';
        }
        $result .= '<li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="' . $TableAdmin->translate('Settings') . '"><i class="fa fa-cog" aria-hidden="true"></i> ' . $TableAdmin->translate('Settings') . '</a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdown">';
        foreach ($TableAdmin->TRANSLATIONS as $key => $value) {
            $result .= '<a class="dropdown-item' . ($key == $_SESSION['language'] ? ' active' : '') . '" href="?' . Tools::urlChange(array('language' => $key)) . '"><i class="fa fa-flag" aria-hidden="true"></i> ' . Tools::h($value) . '</a>' . PHP_EOL;
        }
        $result .=  (isset($_SESSION['user']) ? '<div class="dropdown-divider"></div><a class="dropdown-item" href="" id="toggle-nav" title="' . Tools::h($TableAdmin->translate('Toggle sidebar')) . '"><i class="fa fa-columns"></i> ' . $TableAdmin->translate('Sidebar') . '</a>' : '') . '
                    </div>
                  </li>
                </ul>
                  <!--<form class="form-inline mt-2 mt-md-0">
                    <input class="form-control mr-sm-2" type="text" placeholder="' . $TableAdmin->translate('Search') . '" aria-label="Search">
                    <button class="btn btn-outline-success my-2 my-sm-0" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
                  </form>-->
                </div>
            </nav>';
        return $result;
    }

    /**
     * Output (in HTML) the project-specific links in the navigation section of admin
     *
     * @result string
     */
    protected function outputAdminSpecificLinks()
    {
        return '';
    }

    /**
     * Output (in HTML) the media section of admin
     *
     * @result string
     */
    protected function outputAdminMedia()
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
                    <button type="submit" name="upload-media" value="1" class="btn btb-lg btn-primary"><i class="fa fa-upload" aria-hidden="true"></i> ' . $TableAdmin->translate('Upload') . '</button>
                </fieldset>
            </form><hr />
            <details><summary>' . $TableAdmin->translate('Uploaded files') . '</summary>
            <div id="media-files"></div>
            <button class="btn btn-primary btn-sm mt-3" title="' . $TableAdmin->translate('Delete') . '" id="delete-media-files"><i class="fa fa-check-square" aria-hidden="true"></i> <i class="fa fa-trash-o" aria-hidden="true"></i></button>
            </details>';
        return $result;
    }

    /**
     * Output (in HTML) the user section of admin
     *
     * @result string
     */
    protected function outputAdminUser()
    {
        $TableAdmin = $this->TableAdmin;
        $result = '<h1>' . $TableAdmin->translate('User') . '</h1>';
        // logout
        if (isset($_GET['logout'])) {
            $result .= '<h2><small>' . $TableAdmin->translate('Logout') . '</small></h2>
                <form action="" method="post" id="logout-form" class="panel d-inline-block"><fieldset class="card p-2">
                <button type="submit" name="logout" class="form-control btn-primary text-left"><i class="fa fa-sign-out" aria-hidden="true"></i> ' . $TableAdmin->translate('Logout') . '</button>'
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
                . '<button type="submit" name="change-password" class="btn btn-primary my-3 ml-3"><i class="fa fa-id-card-o" aria-hidden="true"></i> ' . $TableAdmin->translate('Change password') . '</button>
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
                  <button type="submit" name="create-user" class="btn btn-primary my-2"><i class="fa fa-user-plus" aria-hidden="true"></i> ' . $TableAdmin->translate('Create user') . '</button>
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
                            . '<i class="fa fa-user-times" aria-hidden="true"></i></button> '
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
    protected function outputAdminLogin()
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
            <div class="col-sm-9 col-sm-offset-3 my-3"><button type="submit" name="login" class="btn btn-primary"><i class="fa fa-sign-in" aria-hidden="true"></i> ' . $TableAdmin->translate('Login') . '</button>
            </div>
            </form>';
    }

    /**
     * Output (in HTML) the dashboard section of admin.
     *
     * @result string
     */
    protected function outputAdminDashboard()
    {
        $TableAdmin = $this->TableAdmin;
        $result = '<br class="m-3"/><br class="m-3"/><hr />
            <div><small>' . $TableAdmin->translate('For more detailed browsing with filtering etc. you may select one of the following tables…') . '</small></div>
            <div class="detailed-tables">';
        foreach (array_keys($TableAdmin->tables) as $table) {
            if (substr($table, 0, strlen(TAB_PREFIX)) != TAB_PREFIX) {
                continue;
            }
            $result .= '<a href="?table=' . urlencode($table) . '&amp;where[id]="><i class="fa fa-plus-square-o" aria-hidden="true" title="' . $TableAdmin->translate('New record') . '"></i></a> '
                . '<a href="?table=' . urlencode($table) . '" class="d-inline' . ($_GET['table'] == $table ? ' active' : '') . '">'
                . '<i class="fa fa-table" aria-hidden="true"></i> '
                . Tools::h(substr($table, strlen(TAB_PREFIX)))
                . ($table == $_GET['table'] ? ' <span class="sr-only">(current)</span>' : '')
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
    protected function outputAdminAgendas()
    {
        $TableAdmin = $this->TableAdmin;
        // show agendas in the sidebar
        $result = '<details id="agendas"><summary class="page-header">' . $TableAdmin->translate('Agendas') . '<br />'
            . $TableAdmin->translate('Select your agenda, then particular row.') . '</summary><div class="ml-3">' . PHP_EOL;
        foreach ($this->agendas as $agenda => $option) {
            Tools::setifempty($option['table'], $agenda);
            $result .= '<details class="my-1" id="details-' . $agenda . '">
                <summary><i class="fa fa-table" aria-hidden="true"></i> ' . Tools::h(Tools::setifempty($option['display'], $agenda)) . '</summary>
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
    protected function outputAdminBodyEnd()
    {
        $TableAdmin = $this->TableAdmin;
        $result = 
            (empty($this->javascripts) ? '' : ('<script type="text/javascript" src="' . implode('"></script><script type="text/javascript" src="', $this->javascripts) . '"></script>' ))
            //<script type="text/javascript" src="scripts/bootstrap-datetimepicker.js"></script>
            . '<script type="text/javascript" src="scripts/jquery.sha1.js"></script>'
            . '<script type="text/javascript" src="scripts/summernote.js"></script>'
            . '<script type="text/javascript" src="scripts/admin.js?v=' . PAGE_RESOURCE_VERSION . '" charset="utf-8"></script>'
            . '<script type="text/javascript" src="scripts/admin-specific.js?v=' . PAGE_RESOURCE_VERSION . '" charset="utf-8"></script>'
            . '<script type="text/javascript">' . PHP_EOL;
        $tmp = array_flip(explode('|', 'Descending|Really delete?|New record|Passwords don\'t match!|Please, fill necessary data.|Select at least one file and try again.|No files|Edit|variable|value|name|size|modified|Select'));
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
            . '    $("h2 .AdminRecordName").text(AdminRecordName);' . PHP_EOL
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
                        <h5 class="modal-title">Image selector</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <select name="subfolder" id="modalImageFolder" class="form-control form-control-sm" onchange="updateImageSelector($(this), $(this).parent().find(\'.ImageFiles\'))">
                        </select>
                        <div id="modalImageFiles" class="ImageFiles"></div>
                        <label class="note-form-label">Image URL:</label><br />
                        <input class="note-image-url form-control form-control-sm" type="text" id="modalImagePath" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary note-image-url pull-left" id="modalReloadImages"><i class="fa fa-refresh" aria-hidden="true"></i> ' . $TableAdmin->translate('Reload') . '</button>
                        <button type="button" class="btn btn-primary note-image-url" id="modalInsertImage"><i class="fa fa-picture-o" aria-hidden="true"></i> ' . $TableAdmin->translate('Insert') . '</button>
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
    protected function outputAdminTable()
    {
        $TableAdmin = $this->TableAdmin;
        $tablePrefixless = mb_substr($_GET['table'], mb_strlen(TAB_PREFIX));
        $result = '<h1 class="page-header">'
            . '<a href="?table=' . Tools::h($_GET['table']) . '" title="' . $TableAdmin->translate('Back to listing') . '"><i class="fa fa-list-alt" aria-hidden="true"></i></a> '
            . '<tt>' . Tools::h($tablePrefixless) . '</tt></h1>' . PHP_EOL;
        if (isset($_GET['where']) && is_array($_GET['where'])) {
            // table edit
            $result .= '<h2 class="sub-header">' . $TableAdmin->translate('Edit') . ' <span class="AdminRecordName"></span></h2>';
            $tabs = array(null);
            foreach (Tools::set($this->TableAdmin->tableContext['language-versions'], $this->MyCMS->TRANSLATIONS) as $key => $value) {
                $tabs[$value] = "~^.+_$key$~i";
            }
            $result .= $this->outputAdminTableBeforeEdit()
                . $TableAdmin->outputForm($_GET['where'], array(
                    'layout-row' => true,
                    'prefill' => isset($_GET['prefill']) && is_array($_GET['prefill']) ? $_GET['prefill'] : array(),
                    'original' => true,
                    'tabs' => $tabs,
                    'return-output' => 1
                ))
                . $this->outputAdminTableAfterEdit();
        } else {
            // table listing
            $result .= '<h2 class="sub-header">' . $TableAdmin->translate('Listing') . '</h2>'
                . $this->outputAdminTableBeforeListing()
                . $TableAdmin->view(array('return-output'=>1))
                . $this->outputAdminTableAfterListing();
        }
        return $result;
    }

    /**
     * Return if a project-specific sections should be displayed in admin.
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
    protected function outputAdminTableBeforeListing()
    {
        return '';
    }

    /**
     * Output (in HTML) project-specific code after listing of selected table
     *
     * @result string
     */
    protected function outputAdminTableAfterListing()
    {
        return '';
    }

    /**
     * Output (in HTML) project-specific code before editing a record from selected table
     *
     * @result string
     */
    protected function outputAdminTableBeforeEdit()
    {
        return '';
    }

    /**
     * Output (in HTML) project-specific code after editing a record from selected table
     *
     * @result string
     */
    protected function outputAdminTableAfterEdit()
    {
        return '';
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
            . $this->outputAdminHead($tmpTitle)
            . '<body>' . PHP_EOL . '<header>'
            . $this->outputAdminNavigation()
            . '</header>' . PHP_EOL . '<div class="container-fluid">' . PHP_EOL;
        if (isset($_SESSION['user']) && $_SESSION['user']) {
            $output .= '<nav class="col-md-3 bg-light sidebar" id="admin-sidebar">' . $this->outputAdminAgendas() . '</nav>' . PHP_EOL;
        }
        $output .= '<main class="ml-sm-auto col-md-9 pt-3" role="main" id="admin-main">'
            . Tools::showMessages(false);
        foreach (glob(DIR_ASSETS . '*', GLOB_ONLYDIR) as $value) {
            $this->ASSETS_SUBFOLDERS []= substr($value, strlen(DIR_ASSETS));
        }
        // table listing/editing
        if ($_GET['table']) {
            $output .= $this->outputAdminTable();
        }
        // media upload etc.
        elseif (isset($_GET['media'])) {
            $output .= $this->outputAdminMedia();
        }
        // user operations (logout, change password, create user, delete user)
        elseif (isset($_GET['user'])) {
            $output .= $this->outputAdminUser();
        }
        // user not logged in - show a login form
        elseif(!isset($_SESSION['user'])) {
            $output .= $this->outputAdminLogin();
        }
        // project-specific admin sections
        elseif ($this->projectSpecificSectionsCondition()) {
            $output .= $this->projectSpecificSections($TableAdmin);
        } else {
            // no agenda selected, showing "dashboard"
        }
        if (isset($_SESSION['user'])) {
            $output .= $this->outputAdminDashboard();
        }
        $output .= '</main></div>' . PHP_EOL . '<footer class="sticky-footer">&copy; GODS, s r.o. All rights reserved.</footer>';
        if (isset($_SESSION['user'])) {
            $output .= $this->outputImageSelector();
        }
        $output .= $this->outputAdminBodyEnd()
            . '</body>' . PHP_EOL .'</html>';
        return $output;
    }
}
