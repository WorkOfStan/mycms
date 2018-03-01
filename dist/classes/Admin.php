<?php

namespace GodsDev\MYCMSPROJECTNAMESPACE;

use GodsDev\Tools\Tools;
use Tracy\Debugger;
use GodsDev\MyCMS\MyAdmin;
use GodsDev\MyCMS\MyCMS;

class Admin extends MyAdmin
{

    use \Nette\SmartObject;

    /** @var array */
    protected $agendas = array();
    
    /**
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options overides default values of properties
     */
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        parent::__construct($MyCMS, $options);
        //Debugger::barDump($this->get, 'GET');
    }

    /**
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
     * //obsoleted by __construct:// @param \GodsDev\MyCMS\MyCMS $MyCMS
     * //obsoleted by __construct:// @param array $AGENDAS
     * 
     * Outputs echo //@todo use Latte or at least return string to be outputted (not output it directly) so that it can be properly PHPUnit tested
     */
    public function outputAdmin()
    {
        //@todo replace the two local variables by the object wide variables below:
        $MyCMS = $this->MyCMS;
        $AGENDAS = $this->agendas;

        Debugger::barDump($MyCMS, 'MyCMS');
        Debugger::barDump($AGENDAS, 'Agendas');
        Debugger::barDump($_SESSION, 'Session');

        $TableAdmin = new \GodsDev\MyCMS\TableAdmin($MyCMS->dbms, Tools::set($_GET['table']));
        if (!in_array($_GET['table'], array_keys($TableAdmin->tables))) {
            $_GET['table'] = '';
        }
        $tablePrefixless = mb_substr($_GET['table'], mb_strlen(TAB_PREFIX));
        if (!isset($_SESSION['user'])) {
            $_GET['table'] = $_GET['media'] = $_GET['user'] = null;
        }
        //@todo rewrite this to Latté?
        echo '<!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
            <meta name="description" content="">
            <meta name="author" content="">
            <title>' . Tools::h(Tools::wrap($tablePrefixless, '', ' - CMS Admin', 'CMS Admin')) . '</title>
            <link rel="stylesheet" href="styles/bootstrap.css" />
            <style type="text/css">';
        echo $this->getAdminCss();//maybe link rel instead of inline css
            //<link rel="stylesheet" href="styles/admin.css" />//incl. /vendor/godsdev/mycms/styles/admin.css
        echo '</style>
            <link rel="stylesheet" href="styles/ie10-viewport-bug-workaround.css" />
            <link rel="stylesheet" href="styles/bootstrap-datetimepicker.css" />
            <link rel="stylesheet" href="styles/font-awesome.css" />
            <link rel="stylesheet" href="styles/summernote.css" />
            <!--[if lt IE 9]>
            <script type="text/javascript" src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script type="text/javascript" src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->
        </head>
        <body>
        <header>
            <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
                <a class="nav-item mr-2" href="' . $_SERVER['SCRIPT_NAME'] . '">MyCMS</a>
                <button class="navbar-toggler d-lg-none" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarsExampleDefault">
                    <ul class="navbar-nav mr-auto">';
    if (Tools::nonempty($_SESSION['user'])) {
        echo '<li class="nav-item' . (isset($_GET['products']) ? ' active' : '') . '"><a href="?products" class="nav-link"><i class="fa fa-gift" aria-hidden="true"></i> ' . $TableAdmin->translate('Products') . '</a></li>
            <li class="nav-item' . (isset($_GET['pages']) ? ' active' : '') . '"><a href="?pages" class="nav-link"><i class="fa fa-file-text-o" aria-hidden="true"></i> ' . $TableAdmin->translate('Pages') . '</a></li>
            <li class="nav-item' . (isset($_GET['media']) ? ' active' : '') . '"><a href="?media" class="nav-link"><i class="fa fa-video-camera" aria-hidden="true"></i> ' . $TableAdmin->translate('Media') . '</a></li>';
    }
    echo '<li class="nav-item dropdown' . (isset($_GET['user']) ? ' active' : '') . '">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="' . $TableAdmin->translate('User') . '"><i class="fa fa-user" aria-hidden="true"></i> ' . $TableAdmin->translate('User') . '</a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                      <a href="" class="dropdown-item disabled"><i class="fa fa-user" aria-hidden="true"></i> ' . Tools::h($_SESSION['user']) . '</a>
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item' . (isset($_GET['logout']) ? ' active' : '') . '" href="?user&amp;logout"><i class="fa fa-flag" aria-hidden="true"></i> ' . $TableAdmin->translate('Logout') . '</a>
                      <a class="dropdown-item' . (isset($_GET['change-password']) ? ' active' : '') . '" href="?user&amp;change-password"><i class="fa fa-id-card-o" aria-hidden="true"></i> ' . $TableAdmin->translate('Change password') . '</a>
                      <a class="dropdown-item' . (isset($_GET['create-user']) ? ' active' : '') . '" href="?user&amp;create-user"><i class="fa fa-user-plus" aria-hidden="true"></i> ' . $TableAdmin->translate('Create user') . '</a>
                      <a class="dropdown-item' . (isset($_GET['delete-user']) ? ' active' : '') . '" href="?user&amp;delete-user"><i class="fa fa-user-times" aria-hidden="true"></i> ' . $TableAdmin->translate('Delete user') . '</a>
                    </div>
                  </li>
                  <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="' . $TableAdmin->translate('Settings') . '"><i class="fa fa-cog" aria-hidden="true"></i> ' . $TableAdmin->translate('Settings') . '</a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                      <a class="dropdown-item' . ($_SESSION['language'] == 'cs' ? ' active' : '') . '" href="?' . Tools::urlChange(array('language' => 'cs')) . '"><i class="fa fa-flag" aria-hidden="true"></i> ' . $MyCMS->translate('language:cs') . '</a>
                      <a class="dropdown-item' . ($_SESSION['language'] == 'en' ? ' active' : '') . '" href="?' . Tools::urlChange(array('language' => 'en')) . '"><i class="fa fa-flag" aria-hidden="true"></i> ' . $MyCMS->translate('language:en') . '</a>
                    </div>
                  </li>
                </ul>
                  <!--<form class="form-inline mt-2 mt-md-0">
                    <input class="form-control mr-sm-2" type="text" placeholder="' . $TableAdmin->translate('Search') . '" aria-label="Search">
                    <button class="btn btn-outline-success my-2 my-sm-0" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
                  </form>-->
                </div>
            </nav>
        </header>
        <div class="container-fluid">
            <nav class="col-md-3 bg-light sidebar">';
    if (isset($_SESSION['user']) && $_SESSION['user']) {
        // show agendas in the sidebar
        echo '<details id="agendas"><summary class="page-header">' . $TableAdmin->translate('Agendas') . '<br />'
            . $TableAdmin->translate('Select your agenda, then particular row.') . '</summary><div class="ml-3">' . PHP_EOL;
        foreach ($AGENDAS as $agenda => $option) {
            Tools::setifempty($option['table'], $agenda);
            echo '<details class="my-1" id="details-' . $agenda . '">
                <summary><i class="fa fa-table" aria-hidden="true"></i> ' . Tools::h(Tools::setifempty($option['display'], $agenda)) . '</summary>
                <div class="card" id="agenda-' . $agenda . '"></div>
                </details>' . PHP_EOL;
            if (isset($option['prefill'])) {
                $tmp = ',prefill:{';
                foreach ($option['prefill'] as $key => $value) {
                    $tmp .= json_encode($key) . ':' . json_encode($value) . ','; 
                }
                $tmp = substr($tmp, 0, -1) . '}';
            } else {
                $tmp = '';
            }
            $TableAdmin->script .= 'getAgenda(' . json_encode($agenda) . ',{table:' . json_encode($option['table'] ?: $agenda) . $tmp . '});' . PHP_EOL;
        }
        echo '</div></details>';
        $TableAdmin->script .= '$("#agendas > summary").click();';
    }
    echo '</nav>
            <main class="ml-sm-auto col-md-9 pt-3" role="main">';
    Tools::showMessages();
    $ASSETS_SUBFOLDERS = array();
    foreach (glob(DIR_ASSETS . '*', GLOB_ONLYDIR) as $value) {
        $ASSETS_SUBFOLDERS []= substr($value, strlen(DIR_ASSETS));
    }
    
    // table listing/editing
    if ($_GET['table']) {
        echo '<h1 class="page-header">'
            . '<a href="?table=' . Tools::h($_GET['table']) . '" title="' . $TableAdmin->translate('Back to listing') . '"><i class="fa fa-list-alt" aria-hidden="true"></i></a> '
            . '<tt>' . Tools::h($tablePrefixless) . '</tt></h1>' . PHP_EOL;
        if (isset($_GET['where']) && is_array($_GET['where'])) {
            // table editing
            echo '<h2 class="sub-header">' . $TableAdmin->translate('Edit') . '</h2>';
            $tmp = array(null);
            foreach ($MyCMS->TRANSLATIONS as $key => $value) {
                $tmp[$value] = "~^.+_$key$~i";
            }
            $options = array(
                'layout-row' => true, 
                'prefill' => isset($_GET['prefill']) && is_array($_GET['prefill']) ? $_GET['prefill'] : array(),
                'original' => true,
                'tabs' => $tmp
            );
            if ($tablePrefixless == 'category') {
                //$options['exclude-fields'] = array('path');
            }
            $TableAdmin->outputForm($_GET['where'], $options);
            if (isset($_GET['where']['id']) && $_GET['where']['id']) {
                switch ($_GET['table']) {
                    case TAB_PREFIX . 'category':
                        foreach (array('content', 'product') as $i) {
                            if ($tmp = $MyCMS->fetchAndReindex('SELECT id,' . $i . '_' . $_SESSION['language'] . ' FROM ' . TAB_PREFIX . $i . ' WHERE category_id=' . (int)$_GET['where']['id'])) {
                                echo '<hr /><details><summary>' . $TableAdmin->translate($i == 'content' ? 'Content linked to this category' : 'Products linked to this category') . ' <span class="badge badge-secondary">' . count($tmp) . '</span></summary>';
                                foreach ($tmp as $key => $value) {
                                    echo '<a href="?table=' . TAB_PREFIX . $i . '&amp;where[id]=' . $key . '" target="_blank" title="' . $TableAdmin->translate('Link will open in a new window'). '">'
                                        . '<i class="fa fa-external-link" aria-hidden="true"></i></a> ' . Tools::h($value) . '<br />' . PHP_EOL;
                                }
                                echo '</details>';
                            }
                        }
                        break;
                    case TAB_PREFIX . 'product':
                        if ($tmp = $MyCMS->fetchAndReindex('SELECT id,content_' . $_SESSION['language'] . ' AS content,description_' . $_SESSION['language'] . ' AS description FROM ' . TAB_PREFIX . 'content WHERE product_id=' . (int)$_GET['where']['id'])) {
                            echo '<hr /><details><summary>' . $TableAdmin->translate('Content linked to this product') . ' <span class="badge badge-secondary">' . count($tmp) . '</span></summary>';
                            foreach ($tmp as $key => $row) {
                                echo '<a href="?table=' . TAB_PREFIX . 'content&amp;where[id]=' . $key . '" target="_blank" title="' . $TableAdmin->translate('Link will open in a new window'). '">'
                                    . '<i class="fa fa-external-link" aria-hidden="true"></i> ' . Tools::h($row['content']) . ' ' . Tools::h(substr(strip_tags($row['description']), 0, 100)) . '…</a><br />' . PHP_EOL;
                            }
                            echo '</details>';
                        }
                        break;
                }
            }
        } else {
            // table listing
            echo '<h2 class="sub-header">' . $TableAdmin->translate('Listing') . '</h2>';
            switch ($tablePrefixless) {
                case 'content':
                    if (!isset($_GET['col'])) {
                        $TableAdmin->contentByType();
                    }
                    $TableAdmin->view();
                    break;
                default:
                    $TableAdmin->view();
            }
        }
    }
    // media upload etc.
    elseif (isset($_GET['media'])) {
        echo '<h1 class="page-header">' . $TableAdmin->translate('Media') . '</h1>
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
        foreach ($ASSETS_SUBFOLDERS as $value) {
            echo Tools::htmlOption($value, DIR_ASSETS . $value, $_SESSION['assetsSubfolder']);
        }
        echo '</select>
                    <label>' . $TableAdmin->translate('Files') . ':</label>
                    <div id="files-div">
                        <input type="file" name="files[]" class="form-control" onchange="if($(this).val()){$(\'#files-div\').append($(this).prop(\'outerHTML\'));}" />
                    </div>
                    <input type="hidden" name="token" value="' . +$_SESSION['token'] . '"/>
                    <button type="submit" name="upload-media" value="1" class="btn btb-lg btn-primary"><i class="fa fa-upload" aria-hidden="true"></i> ' . $TableAdmin->translate('Upload') . '</button>
                </fieldset>
            </form><hr />
            <details><summary>' . $TableAdmin->translate('Uploaded files') . '</summary>
            <div id="media-files"></div>
            <button class="btn btn-primary btn-sm mt-3" title="' . $TableAdmin->translate('Delete') . '" id="delete-media-files"><i class="fa fa-check-square" aria-hidden="true"></i> <i class="fa fa-trash-o" aria-hidden="true"></i></button>
            </details>';
    }
    // user operations (logout, change password, create user, delete user)
    elseif (isset($_GET['user'])) {
        echo '<h1>' . $TableAdmin->translate('User') . '</h1>';
        // logout
        if (isset($_GET['logout'])) {
            echo '<h2><small>' . $TableAdmin->translate('Logout') . '</small></h2>
                <form action="" method="post" id="logout-form" class="panel d-inline-block"><fieldset class="card p-2">
                <button type="submit" name="logout" class="form-control btn-primary text-left"><i class="fa fa-sign-out" aria-hidden="true"></i> ' . $TableAdmin->translate('Logout') . '</button>'
                . Tools::htmlInput('token', '', $_SESSION['token'], 'hidden') . '
                </fieldset></form>';
        }
        // change password
        if (isset($_GET['change-password'])) {
            echo '<h2><small>' . $TableAdmin->translate('Change password') . '</small></h2>
                <form action="" method="post" id="change-password-form"><fieldset class="card p-2">';
            $options = array(
                'type' => 'password',
                'before' => '<div class="col-sm-3">',
                'between' => '</div><div class="col-sm-9">',
                'after' => '</div>',
                'class' => 'form-control'
            );
            echo Tools::htmlInput('old-password', $TableAdmin->translate('Old password', false) . ':', '', $options + array('id' => 'old-password'))
                . Tools::htmlInput('new-password', $TableAdmin->translate('New password', false) . ':', '', $options + array('id' => 'new-password'))
                . Tools::htmlInput('retype-password', $TableAdmin->translate('Retype password', false) . ':', '', $options + array('id' => 'retype-password'))
                . Tools::htmlInput('token', '', $_SESSION['token'], 'hidden')
                . '<button type="submit" name="change-password" class="btn btn-primary my-3 ml-3"><i class="fa fa-id-card-o" aria-hidden="true"></i> ' . $TableAdmin->translate('Change password') . '</button>
                </fieldset></form>';
        }
        // create user
        if (isset($_GET['create-user'])) {
            echo '<h2><small>' . $TableAdmin->translate('Create user') . '</small></h2>
                <form action="" method="post" class="panel create-user-form"><fieldset class="card p-2">'
                . Tools::htmlInput('token', '', $_SESSION['token'], 'hidden')
                . Tools::htmlInput('user', $TableAdmin->translate('User', false) . ':', '', array('class' => 'form-control', 'id' => 'create-user'))
                . Tools::htmlInput('password', $TableAdmin->translate('Password', false) . ':', '', array('type' => 'password', 'class' => 'form-control', 'id' => 'create-password'))
                . Tools::htmlInput('retype-password', $TableAdmin->translate('Retype password', false) . ':', '', array('type' => 'password', 'class' => 'form-control', 'id' => 'create-retype-password')) . '
                  <button type="submit" name="create-user" class="btn btn-primary my-2"><i class="fa fa-user-plus" aria-hidden="true"></i> ' . $TableAdmin->translate('Create user') . '</button>
                </fieldset></form>';
        }
        // delete user
        if (isset($_GET['delete-user'])) {
            echo '<h2><small>' . $TableAdmin->translate('Delete user') . '</small></h2>';
            if ($query = $MyCMS->dbms->query('SELECT name FROM ' . TAB_PREFIX . 'admin')) {
                echo '<ul class="list-group list-group-flush">';
                while ($row = $query->fetch_assoc()) {
                    echo '<li class="list-group-item">
                        <form action="" method="post" class="form-inline d-inline-block delete-user-form" onsubmit="return confirm(\'' . $TableAdmin->translate('Really delete?') . '\')">' 
                            . Tools::htmlInput('token', '', $_SESSION['token'], 'hidden')
                            . '<button type="submit" name="delete-user" value="' . Tools::h($row['name']) . '"' . ($row['name'] == $_SESSION['user'] ? ' disabled' : '') . ' class="btn btn-primary" title="' . $TableAdmin->translate('Delete user') . '?">'
                            . '<i class="fa fa-user-times" aria-hidden="true"></i> &nbsp; <tt>' . $row['name'] . '</tt></button>
                        </form>
                        </li>' . PHP_EOL;
                }
                echo '</ul>';
            } else {
                echo '<p class="alert alert-warning">' . $TableAdmin->translate('No records found.') . '</p>';
            }
        }
    }
    // user not logged in - show a login form
    elseif(!isset($_SESSION['user'])) {
        $options = array(
            'before' => '<div class="col-sm-3">',
            'between' => '</div><div class="col-sm-9">',
            'after' => '</div>',
            'class' => 'form-control'
        );
        echo '<h1>' . $TableAdmin->translate('Login') . '</h1>
            <form action="" method="post" class="form" id="login-form">
            <div>'
            . Tools::htmlInput('user', $TableAdmin->translate('User', false) . ':', Tools::setifnull($_SESSION['user']), $options)
            . Tools::htmlInput('password', $TableAdmin->translate('Password', false) . ':', '', array('type' => 'password', 'id' => 'login-password') + $options)
            . Tools::htmlInput('token', '', $_SESSION['token'], 'hidden') . '</div>
            <div class="col-sm-9 col-sm-offset-3 my-3"><button type="submit" name="login" class="btn btn-primary"><i class="fa fa-sign-in" aria-hidden="true"></i> ' . $TableAdmin->translate('Login') . '</button>
            </div>
            </form>';
    }
    // products (in categories)
    elseif (isset($_GET['products'])) {
        echo '<h1>' . $TableAdmin->translate('Products') . '</h1><div id="agenda-products">';
        $categories = $MyCMS->fetchAll($sql='SELECT id,category_' . $_SESSION['language'] . ' AS category,active FROM ' . TAB_PREFIX . 'category 
            WHERE LENGTH(path)=' . (strlen($MyCMS->SETTINGS['PATH_CATEGORY']) + PATH_MODULE) . ' AND LEFT(path,' . PATH_MODULE . ')="' . $MyCMS->escapeSQL($MyCMS->SETTINGS['PATH_CATEGORY']) . '" ORDER BY path');
        $products = $MyCMS->fetchAndReindex('SELECT category_id,id,product_' . $_SESSION['language'] . ' AS product,image,sort,active FROM ' . TAB_PREFIX . 'product');
        $perex = $MyCMS->fetchAndReindex('SELECT product_id,id,type,active,TRIM(CONCAT(content_' . $_SESSION['language'] . ', " ", CONCAT(LEFT(description_' . $_SESSION['language'] . ', 50), "…"))) AS content 
            FROM ' . TAB_PREFIX . 'content WHERE type IN ("perex", "claim", "testimonial") AND product_id IS NOT NULL');
        foreach ($categories as $category) {
            echo '<h4' . ($category['active'] == 1 ? '' : ' class="inactive"') . '><a href="?table=' . TAB_PREFIX . 'category&amp;where[id]=' . $category['id'] . '" title="' . $TableAdmin->translate('Edit') . '">'
                . '<i class="fa fa-edit" aria-hidden="true"></i></a> <form method="post" class="d-inline-block">' . Tools::htmlInput('token', '', $_SESSION['token'], 'hidden')
                . '<button type="submit" class="btn btn-sm d-inline" name="category-up" value="' . (int)$category['id'] . '" title="' . $TableAdmin->translate('Move up') . '"><i class="fa fa-arrow-up" aria-hidden="true"></i></button> '
                . '<button type="submit" class="btn btn-sm d-inline" name="category-down" value="' . (int)$category['id'] . '" title="' . $TableAdmin->translate('Move down') . '"><i class="fa fa-arrow-down" aria-hidden="true"></i></button></form> ' 
                . Tools::h($category['category'] ?: 'N/A') . '</h4>' . PHP_EOL;
            $productLine = isset($products[$category['id']]) ? (isset($products[$category['id']][0]) ? $products[$category['id']] : array($products[$category['id']])) : array();
            uasort($productLine, function($a, $b) {return $a['sort'] == $b['sort'] ? 0 : ($a['sort'] < $b['sort'] ? -1 : 1);});
            foreach ($productLine as $product) {
                $tmp = isset($perex[$product['id']]) && is_array($perex[$product['id']]) ? (isset($perex[$product['id']][0]) ? $perex[$product['id']] : array($perex[$product['id']])) : array();
                echo '<details class="ml-4"><summary class="d-inline-block"><a href="?table=' . TAB_PREFIX . 'product&amp;where[id]=' . $product['id'] . '" title="' . $TableAdmin->translate('Edit') . '"><i class="fa fa-edit" aria-hidden="true"></i></a> ' 
                    . '<span' . ($product['active'] ? '' : ' class="inactive"') . '>' . Tools::h($product['product']) . '</span>' 
                    . ' <sup class="badge badge-' . (count($tmp) ? 'secondary' : 'warning') . '"><small>' . count($tmp) . '</small></sup>'
                    . ' <sup class="badge badge-' . (file_exists($product['image']) ? 'secondary' : 'warning') . '" data-toggle="tooltip" data-html="true" title="<img src=\'' . Tools::h($product['image']) . '\' width=\'200\' class=\'img-thumbnail\'/>"><i class="fa fa-image" aria-hidden="true"></i></sup></summary>';
                foreach ($tmp as $row) {
                    echo '<div class="ml-5' . ($row['active'] ? '' : ' inactive') . '"><a href="?table=' . TAB_PREFIX . 'content&amp;where[id]=' . $row['id'] . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
                        . '<sup>' . $row['type'] . '</sup> ' . Tools::h(strip_tags($row['content'])) . '</div>' . PHP_EOL;
                }
                echo '<div class="ml-5"><a href="?table=' . TAB_PREFIX . 'content&amp;where[]=&amp;prefill[type]=perex&amp;prefill[product_id]=' . $product['id'] . '">'
                    . '<i class="fa fa-plus-square-o" aria-hidden="true"></i></a> ' . $TableAdmin->translate('New record') . '</div>' . PHP_EOL;
                echo '</details>' . PHP_EOL;
            }
            echo '<a href="?table=' . TAB_PREFIX . 'product&amp;where[]=&amp;prefill[category_id]=' . $category['id'] . '" class="ml-4">'
                . '<i class="fa fa-plus-square-o" aria-hidden="true"></i></a> ' . $TableAdmin->translate('New record');
        }
        echo '</div>';
    }
    // pages (in categories)
    elseif (isset($_GET['pages'])) {
        echo '<h1>' . $TableAdmin->translate('Pages') . '</h1><div id="agenda-pages">';
        $categories = $MyCMS->fetchAndReindex('SELECT id,path,category_' . $_SESSION['language'] . ' AS category FROM ' . TAB_PREFIX . 'category 
            WHERE LEFT(path, ' . PATH_MODULE . ')="' . $MyCMS->escapeSQL($MyCMS->SETTINGS['PATH_HOME']) . '" ORDER BY path');
        $articles = $MyCMS->fetchAndReindex('SELECT category_id, id, IF(content_' . $_SESSION['language'] . ' = "", LEFT(CONCAT(code, " ", description_' . $_SESSION['language'] . '), 100), content_' . $_SESSION['language'] . ') AS content
            FROM ' . TAB_PREFIX . 'content WHERE category_id > 0');
        foreach ($categories as $key => $value) {
            $tmp = isset($articles[$key][0]) ? count($articles[$key]) : (isset($articles[$key]) ? 1 : 0);
            echo '<details style="margin-left:' . (strlen($value['path']) / PATH_MODULE - 1) . 'em">
                <summary class="d-inline-block">'
                . '<a href="?table=' . TAB_PREFIX . 'category&amp;where[id]=' . $key . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
                . '<a href="index.php?category&id=' . $key . '" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a> ' 
                . $value['category'] 
                . ' <sup class="badge badge-' . ($tmp ? 'info' : 'warning') . '"><small>' . $tmp . '</small></sup></summary>';
            echo '<div class="ml-3">';
            if (isset($articles[$key])) {
                $tmp = isset($articles[$key][0]) ? $articles[$key] : array($articles[$key]);
                foreach ($tmp as $article) {
                    echo '<a href="?table=' . TAB_PREFIX . 'content&amp;where[id]=' . $article['id'] . '"><i class="fa fa-file-o" aria-hidden="true"></i></a> '
                        . strip_tags($article['content']) . '<br />' . PHP_EOL;
                }
            }
            echo '<a href="?table=' . TAB_PREFIX . 'content&amp;where[]=&amp;prefill[category_id]=' . $key . '&amp;prefill[type]=page">'
                . '<i class="fa fa-plus-square-o" aria-hidden="true"></i></a> ' . $TableAdmin->translate('New record') . '</div>';
            echo '</details>' . PHP_EOL;
        }
        $articles = $MyCMS->fetchAndReindex('SELECT 0, id, IF(content_' . $_SESSION['language'] . ' = "", LEFT(CONCAT(code, " ", description_' . $_SESSION['language'] . '), 100), content_' . $_SESSION['language'] . ') AS content
            FROM ' . TAB_PREFIX . 'content WHERE category_id IS NULL AND product_id IS NULL');
        if ($articles) {
            echo '<details><summary><tt>NULL</tt></summary>';
            foreach ($articles[0] as $article) {
                echo '<a href="?table=' . TAB_PREFIX . 'content&amp;where[id]=' . $article['id'] . '" class="ml-3"><i class="fa fa-file-o" aria-hidden="true"></i></a> ' . strip_tags($article['content']) . '<br />' . PHP_EOL;
            }
            echo '</details>';
        }
        echo '</div>';
    } else {
        // no agenda selected, showing "dashboard"
    }
    if (isset($_SESSION['user'])) {
        echo '<br class="m-3"/><br class="m-3"/><hr />
            <div><small>' . $TableAdmin->translate('For more detailed browsing with filtering etc. you may select one of the following tables…') . '</small></div>
            <div class="detailed-tables">';
        foreach (array_keys($TableAdmin->tables) as $table) {
            if (substr($table, 0, strlen(TAB_PREFIX)) != TAB_PREFIX) {
                continue;
            }
            echo  '<a href="?table=' . urlencode($table) . '&amp;where[id]="><i class="fa fa-plus-square-o" aria-hidden="true" title="' . $TableAdmin->translate('New record') . '"></i></a> '
                . '<a href="?table=' . urlencode($table) . '" class="d-inline' . ($_GET['table'] == $table ? ' active' : '') . '">'
                . '<i class="fa fa-table" aria-hidden="true"></i> '
                . Tools::h(substr($table, strlen(TAB_PREFIX)))
                . ($table == $_GET['table'] ? ' <span class="sr-only">(current)</span>' : '')
                . '</a> &nbsp; ' . PHP_EOL;
        }
        echo '</div>';
    }
    /* //test - pro zmenu hierarchie
    echo'<hr /><fieldset id="category-hierarchy">';
    $categories = $MyCMS->fetchAll('SELECT id,path,category_' . $_SESSION['language'] . ' AS title FROM '.TAB_PREFIX.'category 
        WHERE LEFT(path,'.PATH_MODULE.')="'.$MyCMS->SETTINGS['PATH_HOME'].'" 
        ORDER BY path');
    
    function listCategoryLevel($prefix) {
        global $categories;
        foreach ($categories as $category) {
            if (Tools::begins($category['path'], $prefix) && strlen($category['path']) == strlen($prefix) + PATH_MODULE) {
                echo '<details open style="margin-left:'.strlen($category['path']).'px" data-prefix="' . substr($category['path'], 0, -PATH_MODULE) . '" data-prefix="' . substr($category['path'], 0, -PATH_MODULE) . '">'
                    . '<summary><a href="#" class="up">↑</a><a href="#" class="down">↓</a>' . $category['title'] . '</summary><span>';
                listCategoryLevel($category['path']);
                echo '</span></details>';
            }
        }
    }
    listCategoryLevel('');
    echo'</fieldset>';*/
?>
        </main>
    </div>
    <footer class="sticky-footer">
        &copy; GODS, s r.o. All rights reserved.
    </footer>
    <div class="modal" id="image-selector" tabindex="-1" role="dialog" data-type="modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Image selector</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <select name="subfolder" id="modalImageFolder" class="form-control form-control-sm" onchange="updateImageSelector($(this), $(this).parent().find('.ImageFiles'))">
                    </select>
                    <div id="modalImageFiles" class="ImageFiles"></div>
                    <label class="note-form-label">Image URL:</label><br />
                    <input class="note-image-url form-control form-control-sm" type="text" id="modalImagePath" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary note-image-url pull-left" id="modalReloadImages"><i class="fa fa-refresh" aria-hidden="true"></i> <?php echo $TableAdmin->translate('Reload')?></button>
                    <button type="button" class="btn btn-primary note-image-url" id="modalInsertImage"><i class="fa fa-picture-o" aria-hidden="true"></i> <?php echo $TableAdmin->translate('Insert')?></button>
                </div>
            </div>
        </div>
    </div>
<!--<script type="text/javascript" src="https://code.jquery.com/jquery.js"></script>-->
<script type="text/javascript" src="scripts/jquery.js"></script>
<script type="text/javascript" src="scripts/popper.js"></script>
<script type="text/javascript" src="scripts/bootstrap.js"></script>
<!--<script type="text/javascript" src="scripts/bootstrap-datetimepicker.js"></script>-->
<script type="text/javascript" src="scripts/jquery.sha1.js"></script>
<script type="text/javascript" src="scripts/summernote.js"></script>
<script type="text/javascript" src="scripts/admin.js?v=<?php echo PAGE_RESOURCE_VERSION; ?>" charset="utf-8"></script>
<script type="text/javascript">
<?php
$tmp = array_flip(explode('|', 'Descending|Really delete?|New record|Passwords don\'t match!|Please, fill necessary data.|Select at least one file and try again.|No files|Edit|variable|value|name|size'));
foreach ($tmp as $key => $value) {
    $tmp[$key] = $TableAdmin->translate($key, false);
}
echo 'TRANSLATE = ' . json_encode($tmp) . ';
TAB_PREFIX = "' . TAB_PREFIX . '";
EXPAND_INFIX = "' . EXPAND_INFIX . '";
TOKEN = ' . +$_SESSION['token'] . ';
ASSETS_SUBFOLDERS = ' . json_encode($ASSETS_SUBFOLDERS, true) . ';
DIR_ASSETS = ' . json_encode(DIR_ASSETS, true) . ';
$(document).ready(function(){
    ' . $TableAdmin->script . '
})'; ?>
</script>
    </body>
</html><?php
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
