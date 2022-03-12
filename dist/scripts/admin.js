/* global $, ASSETS_SUBFOLDERS, DIR_ASSETS, LISTED_FIELDS, TAB_PREFIX, TOKEN, TRANSLATE, WHERE_OPS */

/**
 * JavaScript client-side of MyCMS admin
 * dependent JS: jquery.js, jquery.sha1.js
 * dependent variables: TOKEN, LISTED_FIELDS, ASSETS_SUBFOLDERS, WHERE_OPS
 */

let sortIndex = 0;
let searchIndex = 0;
let imageSelectorTarget = '';
let vDivider = 25;
let vDividerMoving = false;
let agendas = []; // globally used variable

function prepareDatetimepicker(date, time)
{
    let timeformat = (date ? 'dd-MM-yyyy' : '') + (date && time ? ' ' : '') + (time ? 'hh:mm:ss' : '');
    $('input.input-' + (date ? 'date' : '') + (time ? 'time' : '')).each(function () {
        let div = $('<div class="input-append date">' + $(this).attr('data-format', timeformat)[0].outerHTML
                + '<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-' + (date ? 'calendar' : 'time') + '"></i></span></div>');
        $(this).hide();
        div.insertAfter($(this));
        $(this).remove();
        div.datetimepicker({
            format: timeformat,
            pickDate: date,
            pickTime: time
        });
    });
}

// sort control element in table view
function addSortRow(target, field, descending)
{
    field = parseInt(field);
    if (field < 0 || field > LISTED_FIELDS.length) {
        return false;
    }
    let html = '<div><select name="sort[' + sortIndex + ']" class="select-sort" title="' + TRANSLATE['Select'] + '" onchange="if(!$(this).parent().next().length){addSortRow($(this).parent().parent(), null, false);}">\n<option />\n';
    for (i in LISTED_FIELDS) {
        html += '<option value="' + (i - 0 + 1) + '"' + ((i - 0 + 1) == field ? ' selected="selected"' : '') + '>' + LISTED_FIELDS[i] + '</option>\n';
    }
    html += '</select>\n'
            + '<label data-toggle="tooltip" title="' + TRANSLATE['Descending'] + '"><input type="checkbox" name="desc[' + sortIndex + ']"' + (descending ? ' checked="checked"' : '') + ' /> '
            + '<span class="glyphicon glyphicon-sort-by-attributes-alt fa fa-sort-amount-down" aria-hidden="true"/></label></div>';
    $(target).append(html);
    sortIndex++;
    return true;
}

// search control element in table view
function addSearchRow(target, field, op, value)
{
    field = parseInt(field);
    if (field < 0 || field > LISTED_FIELDS.length) {
        return false;
    }
    html = '<div><select name="col[' + searchIndex + ']" class="select-search"  title="' + TRANSLATE['Select'] + '" onchange="if(!$(this).parent().next().length){addSearchRow($(this).parent().parent(), null, 0, \'\');}">\n<option />\n';
    for (i in LISTED_FIELDS) {
        html += '<option value="' + (i - 0 + 1) + '"' + (field == (i - 0 + 1) ? ' selected="selected"' : '') + '>' + LISTED_FIELDS[i] + '</option>\n';
    }
    html += '</select>\n<select name="op[' + searchIndex + ']" class="select-op" title="' + TRANSLATE['Select'] + '">\n';
    for (i in WHERE_OPS) {
        html += '<option value="' + i + '"' + (op == i ? ' selected="selected"' : '') + '>' + WHERE_OPS[i] + '</option>\n';
    }
    html += '</select>\n<input type="search" name="val[' + searchIndex + ']" value="' + value + '" size="8" class="form-control form-control-sm d-inline" /></div>';
    $(target).append(html);
    searchIndex++;
    return true;
}

function urlChange(changes)
{
    let pairs = location.search.substr(1).split('&');
    let tmp = {};
    for (i in pairs) {
        let pair = pairs[i].split('=');
        if (Object.keys(changes).indexOf(pair[0]) == -1) {
            tmp[pair[0]] = pair[1];
        } else if (typeof (changes[pair[0]]) != 'undefined') {
            tmp[pair[0]] = pair[1];
        }
    }
    for (i in changes) {
        if (typeof (changes[i]) != 'undefined') {
            tmp[i] = changes[i];
        }
    }
    let result = '';
    for (i in tmp) {
        result += '&' + i + '=' + tmp[i];
    }
    return '?' + result.substr(1);
}

function toggleTableColumn(/*element*/table, /*int*/column, /*bool*/show)
{
    var rows = $(table).find('tr');
    $(rows).each(function (row) {
        if (typeof (rows[row].cells[column]) != "undefined") {
            $(rows[row].cells[column]).toggle(show);
        }
    });
}

function getAgenda(agenda, options)
{
    var option = options;
    $.ajax({
        url: '?keep-token',
        dataType: 'json',
        data: {
            'agenda': agenda,
            'token': TOKEN
        },
        type: 'POST',
        success: function (data) {
            if (data.success) {
                fillAgenda(data, option);
            }
        }
    });
}

function fillAgenda(data, options)
{
    let prefill = '';
    let html = '';
    for (i in data.data) {
        html += agendaRow(data, i, options);
    }
    let agenda = $('#agenda-' + data.agenda);
    if (typeof (options.prefill) == 'object') {
        for (i in options.prefill) {
            prefill += '&amp;prefill[' + i + ']=' + options.prefill[i];
        }
    }
    agenda.html(html + '<div class="m-1"><a href="?table=' + TAB_PREFIX + options.table + '&amp;where[]=' + prefill + '" class="pl-1"><i class="fa fa-plus-square" aria-hidden="true"></i></a> &nbsp; ' + TRANSLATE['New record'] + '</div>');
}

// content rows in agenda column
function agendaRow(data, index, options)
{
    let row = data.data[index];
    let result = '<div class="m-1" data-id="' + row.id + '" data-table="' + data.agenda + '">\n'
            + '<a href="?table=' + TAB_PREFIX + (options['table'] || data.agenda) + '&amp;where[id]=' + row.id + '"'
            + ' class="btn btn-link btn-xs" title="edit"><i class="fa fa-pencil-alt" aria-hidden="true"></i></a>\n';
    if (row.sort) {
        result += '<button data-dir="-1" class="btn btn-secondary btn-xs btn-sort" title="move up" onclick="sortButtonOnClick(this)"' + (index == 0 ? ' disabled' : '') + '><i class="fa fa-long-arrow-up" aria-hidden="true"></i></button>\n'
                + '<button data-dir="1" class="btn btn-secondary btn-xs btn-sort" title="move down" onclick="sortButtonOnClick(this)"' + (index == data.data.length - 1 ? ' disabled' : '') + '><i class="fa fa-long-arrow-down" aria-hidden="true"></i></button>\n';
    }
    if (data.subagenda) {
        result += '<a href="#" class="btn btn-xs btn-link btn-expand" data-toggle="collapse" data-target="#agenda-' + data.agenda + '-' + row.id + '" aria-expanded="false" aria-controls="agenda-' + data.agenda + '-' + row.id + '" title="expand"><i class="fa fa-caret-down" aria-hidden="true"></i></a>\n';
    }
    result += '<span class="item-name">' + row['name'] + '</span>';
    if (row.join && data.subagenda) {
        result += '<div class="ml-3 my-1 border rounded p-1 alert-secondary subagenda collapse" id="agenda-' + data.agenda + '-' + row.id + '" data-id="' + row.id + '">\n';
        if (typeof (row.join[0]) == "undefined") {
            row.join = [row.join];
        }
        for (j in row.join) {
            result += '<div data-id="' + row.join[j].id + '" data-table="' + data.subagenda + '">'
                    + '<a href="?table=' + TAB_PREFIX + data.subagenda + '&amp;where[id]=' + row.join[j]['id'] + '" class="btn btn-link btn-xs" title="edit"><i class="fa fa-pencil-alt" aria-hidden="true"></i></a>\n';
            if (row.join.sort && row.join.length > 1) {
                result += '<button data-dir="-1" class="btn btn-secondary btn-xs btn-sort" title="move up" onclick="sortButtonOnClick(this)"' + (j == 0 ? ' disabled' : '') + '><i class="fa fa-long-arrow-up" aria-hidden="true"></i></button>\n' // or <i class="fas fa-long-arrow-alt-up"></i>
                        + '<button data-dir="1" class="btn btn-secondary btn-xs btn-sort" title="move down" onclick="sortButtonOnClick(this)"' + (j == row.join.length - 1 ? ' disabled' : '') + '><i class="fa fa-long-arrow-down" aria-hidden="true"></i></button>\n'; // or <i class="fas fa-long-arrow-alt-down"></i>
            }
            result += '<span class="item-name">' + row.join[j]['name'] + '</span></div>';
        }
        result += '</div>\n';
    }
    return result + '</div>\n';
}

function updateImageSelector(ImageFolder, ImageFiles)
{
    //console.log('updateImageSelector', ImageFolder, ImageFiles);
    $(ImageFiles).html('<img src="images/loader.gif" />'); // or <i class="fas fa-spinner fa-spin"></i>
    $.ajax({
        url: '?keep-token',
        dataType: 'json',
        data: {
            'subfolder': $(ImageFolder).val(),
            'media-files': 1,
            // 'info': 1, // TODO use this line or not??
            'token': TOKEN,
            'wildcard': '*.{jpg,gif,png}'
        },
        type: 'POST',
        success: function (data) {
            if (data.success) {
                let html = '';
                for (i in data.data) {
                    let filename = data.data[i]['name'] + data.data[i]['extension'];
                    let src = data.subfolder + '/' + filename;
                    html += '<a href="' + src + '" title="' + filename + (typeof(data.data[i]['info']) != "undefined" ? "\n" + data.data[i]['info'] : "") + '">'
                            + '<img src="' + src + '" data-src="' + src + '" />\n'
                            + '<span>' + filename + '</span></a>\n';
                }
                $(ImageFiles).html(html);
            }
            $(ImageFiles).find('a').on('click', function (event) {
                event.preventDefault();
                let tmp = $(ImageFolder).parent().parent();
                tmp.find('.note-image-url').val($(this).find('img').data('src'));
                tmp.parent().parent().find('.modal-footer button.btn-primary').click();
            });
        }
    });
}

function fillAssetsSubfolders(element)
{
    $(element).append($('<option>', {value: '', text: DIR_ASSETS}));
    for (i in ASSETS_SUBFOLDERS) {
        $(element).append($('<option>', {
            value: ASSETS_SUBFOLDERS[i],
            text: DIR_ASSETS + ASSETS_SUBFOLDERS[i]
        }));
    }
}

function jsonExpandedTableAddRow(table)
{
    let html = '<tr><td class="first w-25"><input class="form-control form-control-sm" type="text" name="' + EXPAND_INFIX + 'context[]" onblur="jsonExpandedOnBlur(this)" placeholder="' + TRANSLATE['variable'] + '"></td>'
            + '<td class="second w-75"><input class="form-control form-control-sm" type="text" name="' + EXPAND_INFIX + EXPAND_INFIX + 'context[]" onblur="jsonExpandedOnBlur(this)" placeholder="' + TRANSLATE['value'] + '"></td></tr>';
    $(html).appendTo(table);
}

function jsonExpandedOnBlur(element)
{
    let tr = $(element).parent().parent();
    let first = tr.find('.first input');
    let second = tr.find('.second input');
    let lastRow = tr.index() + 1 == tr.parent().find('tr').length;
    if (first.val() == '' && second.val() == '') {
        if (!lastRow) {
            tr.remove();
        }
    } else if (lastRow) {
        jsonExpandedTableAddRow(tr.parent());
    }
}

// toggle null checkbox according to select/textarea content
function selectWithNullOnChange(element, name)
{
    $('.database input[name=fields-null\\[' + name + '\\]]').prop('checked', !$(element).val());
}

// toggle null checkbox according to "pure" content
function selectWithNullOnChangeContent(contents, name)
{
    // '<p><br></p>' is returned with empty Summernote textarea
    $('.database input[name=fields-null\\[' + name + '\\]]').prop('checked', contents === '<p><br></p>' || contents === '');
}

function pad0(input, len)
{
    return '0'.repeat(len - String(input).length) + input;
}

function moveCategory(element, up)
{
    let prefix = $(element).data('prefix');
    let siblings = $(element).parent().find('details[data-prefix=' + prefix + ']');
    for (i in siblings) {
        if ($(siblings[i])[0] == $(element)[0]) {
            if (up) {
                ;
            } else {
                ;
            }
            break;
        }
    }
}

// toggles Export, Edit, Clone buttons based on row selection
function updateNumberOfSelectedRows(element)
{
    var checked = $(element).closest('table').find('.multi-options input[type=checkbox]:checked').length;
    let form = $(element).closest('form');
    checked = form.find('.table-admin .multi-options input[type=checkbox]:checked').length;
    form.find('.selected-rows .listed').text(checked);
    form.find('.selected-rows button').attr('disabled', checked == 0 && !form.find('.selected-rows .total-rows').is(':checked'));
    return checked;
}

function addMediaMessage(message)
{
    $('#media-file-feedback span:first').text(message).parent().show();
}

// TODO explore code A
function changePasswordSubmit()
{
    let oldPass = $('#old-password').val();
    let newPass = $('#new-password').val();
    let retypePass = $('#retype-password').val();
    let message = (!oldPass || !newPass || !retypePass ? 'Please, fill necessary data.' :
        (newPass.length < 3 || newPass.length > 80 ? 'Wrong input' :
            (newPass != retypePass ? 'Passwords don\'t match!' : '')
        )
    );
    if (message) {
        $('#change-password-message').text(TRANSLATE[message]).parent().show();
        $('#old-password').focus();
        return false;
    }
}

// TODO explore code A
function createUserSubmit()
{
    let user = $('#create-user').val();
    let newPass = $('#create-password').val();
    let retypePass = $('#create-retype-password').val();
    let message = (!user || !newPass || !retypePass ? 'Please, fill necessary data.' :
        (newPass.length < 3 || newPass.length > 80 ? 'Wrong input' :
            (newPass != retypePass ? 'Passwords don\'t match!' : '')
        )
    );
    if (message) {
        $('#create-user-message').text(TRANSLATE[message]).parent().show();
        $('#create-user').focus();
        return false;
    }
}

// TODO explore code A
function adminActivity()
{
    let table = $('form.record-form input[type=hidden][name=table]').val();
    let id = $('form.record-form input[name=fields\\[id\\]]').val();
    if (table && id) {
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'activity': 1,
                'table': table,
                'id': id,
                'token': TOKEN
            },
            type: 'POST',
            success: function (data) {
                $('#realtime-message').data('content', 'abc').popover('show');
                while (typeof($('#user-menu').children()[6]) == 'object') {
                    $('#user-menu').children()[6].remove();
                }
            }
        });
    }
}

function standardDocumentReady()
{
    String.prototype.replaceAll = function (target, replacement) {
        return this.split(target).join(replacement);
    };
    agendas = localStorage.getItem("agendas"); // array of elements that were expanded
    if (agendas) {
        agendas = agendas.split(' ');
    } else {
        agendas = [];
    }

    $('[data-toggle="tooltip"]').tooltip();
    $('input[data-order]').on('click', function (event) {
        let checkboxOrder = $(this).closest('table').data('order');
        if (event.shiftKey) {
            if (checkboxOrder != null) {
                let b = $(this).data('order');
                let checked = $('input[data-order=' + checkboxOrder + ']').prop('checked');
                $.each($(this).closest('table').find('input[data-order]'), function (key, value) {
                    let o = $(this).data('order');
                    checkboxOrder = $(this).closest('table').data('order');
                    if (o >= Math.min(b, checkboxOrder) && o <= Math.max(b, checkboxOrder)) {
                        $(this).prop('checked', checked);
                    }
                });
            }
        }
        $(this).closest('table').data('order', $(this).data('order'));
    });
    //@todo go to page
    $('#go-to-page').on('click', function () {
        let page = prompt('Stránka:');
        if (!isNaN(page) && page > 0 && page < $(this).data('pages')) {
            //TODO debug//console.log('@todo go to page: ' + page);//...
        }
    });
    //date/time picker
    prepareDatetimepicker(false, true);
    //add 'column=...' condition to search fieldset // TODO explore A code
    $('.table-admin thead tr th a.filter').on('click', function (event) {
        event.preventDefault();
        var rand = $(this).parentsUntil(null, 'form').data('rand');
        var search = $('#search-div' + rand);
        search.show();
        addSearchRow(search, 0, 0, '');
        for (i = 0; i < search.find('div').length; i++) {
            if (search.find('.select-search:nth(' + i + ')').val() == '') {
                search.find('.select-search:nth(' + i + ')').val($(this).data('column'));
                search.find('div:nth(' + i + ') input[type=search]').focus();
                break;
            }
        }
    });
    //summernote
    $('textarea.richtext').summernote({
        height: 300,
        minHeight: null,
        maxHeight: null,
        focus: true, // set focus to editable area after initializing summernote
        lang: 'en-US',
        placeholder: 'Edit...',
        tabsize: 8,
        toolbar: [
            ['Style', ['style', /*'fontname', 'fontsize', 'color',*/ 'clear']],
            ['Text', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript']],
            ['Paragraph', ['ol', 'ul', 'paragraph', 'height']],
            ['Insert', ['picture', 'link', 'video', 'table', 'hr']],
            ['Misc', ['undo', 'redo', 'codeview', 'fullscreen', 'help']]
        ],
        callbacks: {
            onInit: function () {
                // add custom icons with events
                //var myBtn = '<button id="mySummernoteTool" type="button" class="btn btn-default btn-sm btn-small" title="Custom button" data-event="something" tabindex="-1"><i class="fa fa-wrench"></i></button>';
                //var btnGroup = '<div class="note-Misc btn-group">' + myBtn + '</div>';
                //$(btnGroup).appendTo($('.note-toolbar'));
                $('#mySummernoteTool').tooltip({container: 'body', placement: 'bottom'}); // Button tooltips
                //$('#mySummernoteTool').click(function (event) { // Button events
                //    // insert code
                //});
            }
        }
    });
    // media - show files on subfolder change
    $('#subfolder').on(
        'change',
        function () {
            $.ajax({
                url: '?keep-token',
                dataType: 'json',
                data: {
                    'subfolder': $(this).val(),
                    'media-files': 1,
                    // 'info': 1, // TODO use this??
                    'token': TOKEN
                },
                type: 'POST',
                success: function (data) {
                    if (data.success) {
                        let path = $('#subfolder option:first-child').text() + $('#subfolder').val() + '/';
                        let html = '';
                        for (i in data.data) {
                            let filename = data.data[i]['name'] + data.data[i]['extension'];
                            let src = data.subfolder + '/' + filename;
                            html += '<tr><td class="multi-options"><input type="radio" name="file" value="' + filename + '"> <input type="checkbox" name="files[]" value="' + filename + '" id="file-' + i + '"></td>'
                                + '<td><a href="' + path + filename + '" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a></td>'
                                + '<td><tt><label for="file-' + i + '">' + data.data[i]['name'] + '</label></tt></td>'
                                + '<td><tt><label for="file-' + i + '">' + data.data[i]['extension'] + '</label></tt></td>'
                                + '<td class="text-right pl-2"><tt><label for="file-' + i + '">' + data.data[i]['size'] + '</label></tt></td>'
                                + '<td class="pl-2"><tt><label for="file-' + i + '">' + data.data[i]['modified'] + '</label></tt></td></tr>\n';
                        }
                        $('#media-files').html(html ? '<table class="subfolder-files"><thead>'
                            + '<tr><th class="multi-options"><input type="radio" name="file" value=""></th><th />'
                            + '<th colspan="2">' + TRANSLATE['name'] + '</th>'
                            + '<th class="text-right">' + TRANSLATE['size'] + '</th>'
                            + '<th class="text-right">' + TRANSLATE['modified'] + '</th></tr></thead>'
                            + html + '</table>'
                            : '<i>' + TRANSLATE['No files'] + '</i>');
                        $('#delete-media-files,#rename-fieldset,#file-rename-folder').toggle(data.data.length > 0);
                        // $('#delete-media-files,#filename-fieldset,#file-rename-folder,#unpack-media-file').addClass('disabled'); // TODO instead of previous line??
                        $('#media-files .subfolder-files .multi-options input[type=radio][name=file]').on('change', function (event) {
                            $('#media-file-name').val($(this).val()).attr('title', $(this).val());
                            // TODO explore A code below
                            if ($(this).val()) {
                                $('#rename-media-file').removeClass('disabled');
                            } else {
                                $('#rename-media-file').addClass('disabled');
                            }
                            if ($(this).val().substr(-4) == '.zip') {
                                $('#unpack-media-file').removeClass('disabled');
                            } else {
                                $('#unpack-media-file').addClass('disabled');
                            }
                        });
                        $('#media-files .subfolder-files .multi-options input[type=checkbox]').on('change', function (event) {
                            if ($('#media-files .subfolder-files tr td input[type=checkbox]:checked').length) {
                                $('#delete-media-files').removeClass('disabled');
                                $('#pack-media-files').removeClass('disabled');
                            } else {
                                $('#delete-media-files').addClass('disabled');
                                $('#pack-media-files').addClass('disabled');
                            }
                        });
                    }
                    $('#media-files').parent().find('summary small.badge').text($('#media-files table.subfolder-files tr').length - 1);
                }
            });
            $('#file-rename-folder').val($(this).val());
        }
    );
    // rename a file
    $('#rename-media-file').on('click', function (event) {
        $('#file-rename-feedback').hide();
        // $('#media-feedback').hide(); // TODO instead of the line above??
        var old_name = $('#media-files .subfolder-files .multi-options input[type=radio][name=file]:checked').val();
        var new_name = $('#media-file-name').val();
        if (!new_name || old_name == new_name) {
            $('#file-rename-feedback').text(TRANSLATE['Please, choose a new name.']).show();
            // addMediaMessage(TRANSLATE['Please, choose a new name.']); // TODO instead of the line above??
            return false;
        }
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'file_rename': new_name,
                'old_name': old_name,
                'subfolder': $('#subfolder').val(),
                'new_folder': $('#file-rename-folder').val(), // TODO really use this line??
                'token': TOKEN
            },
            type: 'POST',
            success: function (data) {
                if (data.success) {
                    location.reload();
                } else {
                    $('#file-rename-feedback').text(data.error).show();
                    // addMediaMessage(data.messages); // TODO instead of the line above??
                }
            }
        });
    });
    // delete file(s)
    $('#delete-media-files').on('click', function (event) {
        let files = [];
        $.each($('#media-files > table.subfolder-files input[type=checkbox]:checked'), function (index, value) {
            files.push($(value).val());
        });
        if (files.length == 0) {
            alert(TRANSLATE['Select at least one file and try again.']);
            // addMediaMessage(TRANSLATE['Select at least one file and try again.']); // TODO instead of the line above??
            return false;
        }
        if (!confirm(TRANSLATE['Really delete?'] + ' (' + files.length + ')')) {
            return false;
        }
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'subfolder': $('#subfolder').val(),
                'delete-files': files,
                'token': TOKEN
            },
            type: 'POST',
            success: function (data) {
                if (data.success) {
                    location.reload();
                } else {
                    addMediaMessage(data.messages);
                }
            }
        });
    });
    // pack file(s)
    $('#pack-media-files').on('click', function (event) {
        let files = [];
        $.each($('#media-files > table.subfolder-files input[type=checkbox]:checked'), function (index, value) {
            files.push($(value).val());
        });
        if (files.length == 0) {
            addMediaMessage(TRANSLATE['Select at least one file and try again.']);
            return false;
        }
        if ($('#media-file-name').val().substr(-4) != '.zip') {
            addMediaMessage(TRANSLATE['Please, fill up a valid file name.']);
            $('#media-file-name').focus();
            return false;
        }
        if (!confirm(TRANSLATE['Really?'] + ' (' + files.length + ')')) {
            return false;
        }
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'subfolder': $('#subfolder').val(),
                'archive': $('#media-file-name').val(),
                'pack-files': files,
                'token': TOKEN
            },
            type: 'POST',
            success: function (data) {
                if (data.success) {
                    location.reload();
                } else {
                    addMediaMessage(data.messages);
                }
            }
        });
    });
    // unpack a file
    $('#unpack-media-file').on('click', function (event) {
        $('#media-feedback').hide();
        var file_archive = $('#media-file-name').val();
        if (!file_archive) {
            addMediaMessage(TRANSLATE['Please, choose a new name.']);
            return false;
        }
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'file_unpack': $('#media-file-name').val(),
                'subfolder': $('#subfolder').val(),
                'new_folder': $('#file-rename-folder').val(),
                'token': TOKEN
            },
            type: 'POST',
            success: function (data) {
                if (data.success) {
                    location.reload();
                } else {
                    addMediaMessage(data.messages);
                }
            }
        });
    });
    //sha1 password before login
    if (typeof ($.sha1) == 'function') {
        $('#login-form').on('submit', function () {
            $('#login-password').val($.sha1($('#login-password').val()));
            return true;
        });
        $('#change-password-form').on('submit', function () {
            if (!$('#old-password').val() || !$('#new-password').val() || !$('#retype-password').val()) {
                alert(TRANSLATE['Please, fill necessary data.']); // TODO use addMediaMessage instead?
                return false;
            }
            if ($('#new-password').val() != $('#retype-password').val()) {
                alert(TRANSLATE['Passwords don\'t match!']); // TODO use addMediaMessage instead?
                return false;
            }
            $('#old-password').val($.sha1($('#old-password').val()));
            $('#new-password').val($.sha1($('#new-password').val()));
            $('#retype-password').val($.sha1($('#retype-password').val()));
            return true;
        });
        $('.create-user-form').on('submit', function () {
            if (!$('#create-user').val() || !$('#create-password').val() || !$('#create-retype-password').val()) {
                alert(TRANSLATE['Please, fill necessary data.']); // TODO use addMediaMessage instead?
                return false;
            }
            if ($('#create-password').val() != $('#create-retype-password').val()) {
                alert(TRANSLATE['Passwords don\'t match!']); // TODO use addMediaMessage instead?
                return false;
            }
            $('#create-password').val($.sha1($('#create-password').val()));
            $('#create-retype-password').val($.sha1($('#create-retype-password').val()));
            return true;
        });
    }
    // record form - Ctrl+Enter in any <input> or <textarea> submits the form
    $('.record-form input, .record-form textarea').on('keyup', function (event) {
        if (event.key == 'Enter' && event.keyCode == 13 && event.ctrlKey) {
            if (event.shiftKey) {
                $(this).closest('form').find('.form-actions input[type=hidden][name=after]').val('return');
            }
            $(this).closest('form').find('.form-actions button[name=record-save]').click();
        }
    });
    // table form - indicate overflow in textareas with maxlength
    $('.database textarea[data-maxlength]').on('keyup', function (event) {
        $(this).toggleClass('is-invalid', (len = String($(this).val()).length) > (maxlen = $(this).data('maxlength')));
        let limit = $(this).nextAll().filter('i.input-limit');
        if (typeof (limit) != "undefined" && typeof (limit[0]) != "undefined") {
            limit[0].title = len + '/' + maxlen;
        }
    });
    // toggles Export, Edit, Clone buttons based on row selection
    $('table.table-admin thead input[type=checkbox].check-all').on('change', function () {
        // "check all" checkbox
        $(this).closest('table').find('tr td.multi-options input[type=checkbox]').prop('checked', $(this).prop('checked'));
        updateNumberOfSelectedRows(this);
    });
    // toggles Export, Edit, Clone buttons based on row selection
    $('table.table-admin tr .multi-options input[type=checkbox]').on('change', function (event) {
        updateNumberOfSelectedRows(this);
    });
    $('textarea.richtext').summernote({
        height: 200,
        minHeight: null,
        maxHeight: null,
        focus: true,
        lang: 'en-US',
        placeholder: 'Edit...',
        toolbar: [
            ['Style', ['style', /*'fontname', 'fontsize', 'color',*/ 'clear']],
            ['Text', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'big']],
            ['Paragraph', ['ol', 'ul', 'paragraph', 'height']],
            ['Insert', ['picture', 'link', /*'video',*/ 'table', 'hr']],
            ['Misc', ['undo', 'redo', 'codeview', 'fullscreen', 'specialchars', 'help']]
        ]
    });
    fillAssetsSubfolders($('#summernoteImageFolder'));
    fillAssetsSubfolders($('#modalImageFolder'));
    fillAssetsSubfolders($('#file-rename-folder'));
    $('#file-rename-folder').val($('#subfolder').val());
    $('#subfolder').change();
    $('button.ImageSelector').on('click', function (event) {
        event.preventDefault();
        imageSelectorTarget = $(this).data('target');
        $('#image-selector').modal();
    });
    $('#modalInsertImage').on('click', function (event) {
        $(imageSelectorTarget).val($('#modalImagePath').val());
        $('#image-selector').modal('hide');
    });
    // TODO explore A code
    $('#modalReloadImages').on('click', function (event) {
        updateImageSelector($('#modalImageFolder'), $(this).parentsUntil(null, '.modal-content').find('.ImageFiles'));
    });
    $('button.btn-webalize').on('click', function (event) {
        event.preventDefault();
        let url = $(this).data('url');
        let name = $(this).data('name');
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'webalize': $('#' + name).val(),
                'table': $(this).data('table')
            },
            type: 'POST',
            success: function (data) {
                if (data.success) {
                    $('#' + url).val(data.data);
                    $('#' + url).change();
                }
            }
        });
    });
    $('div.modal[data-type]').on('shown.bs.modal', function (event) {
        if ($(this).find('#summernoteImageFolder') && $(this).find('#summernoteImageFolder option').length == 0) {
            fillAssetsSubfolders($(this).find('#summernoteImageFolder'));
        }
    });
    $('.json-expanded td.first input, .json-expanded td.second input').on('blur', function (event) {
        jsonExpandedOnBlur(this);
    });
    $('.json-reset').on('click', function (event) {
        event.preventDefault();
        let field = $(this).data('field');
        $(this).parent().find('textarea[name=fields\\[' + field + '\\]]').replaceWith(table = $('<table class="w-100 json-expanded" data-field="' + field + '"></table>'));
        jsonExpandedTableAddRow(table);
        $(this).replaceWith('');
        $(table).find('td:first input').focus();
    });
    $('.btn-fill-now').on('click', function (event) {
        event.preventDefault();
        let d = new Date();
        let now = d.getFullYear() + '-' + pad0(d.getMonth() + 1, 2) + '-' + pad0(d.getDate(), 2) + 'T' + pad0(d.getHours(), 2) + ':' + pad0(d.getMinutes(), 2) + ':' + pad0(d.getSeconds(), 2);
        $(this).parent().parent().find('input').val(now);
    });
    $('.btn-id-unlock').on('click', function (event) {
        event.preventDefault();
        let input = $(this).parent().parent().find('input');
        input.prop('readonly', input.prop('readonly') ? false : 'readonly');
    });
    $('.user-activate').on('change', function (event) {
        event.preventDefault();
        let checkbox = $(this);
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'activate-user': $(checkbox).val(),
                'active': $(checkbox).prop('checked') ? 1 : 0,
                'token': TOKEN
            },
            type: 'POST',
            success: function (data) {
                if (!data.success) {
                    $(checkbox).attr('checked', !$(checkbox).prop('checked'));
                    $('#activate-user-message').text(data.messages).parent().show(); // TODO really use this?
                }
            }
        });
    });
    // save content of summernote editor even if in codeView
    $('.note-codable').on('blur', function () {
        $(this).closest('.TableAdminTextarea').find('textarea:first-child').val($(this).val());
    });
    // toggle sidebar from top menu (TODO: enable in MyAdmin::outputNavigation)
    $('#toggle-nav').on('click', function (event) {
        event.preventDefault();
        $('#admin-sidebar').toggle();
        if ($('#admin-sidebar').is(':visible')) {
            $('#admin-main').addClass('col-md-9').addClass('ml-sm-auto');
            $(this).find('i').removeClass('fa-caret-right').addClass('fa-caret-left');
        } else {
            $('#admin-main').removeClass('col-md-9').removeClass('ml-sm-auto');
            $(this).find('i').removeClass('fa-caret-left').addClass('fa-caret-right');
        }
    });
    // show/hide table columns
    $('.toggle-div input[type=checkbox]').on('change', function (event) {
        let rand = $(this).closest('.toggle-div').data('rand');
        toggleTableColumn($('#table-admin' + rand), $(this).data('column'), $(this).prop('checked'));
    });
    $('.database .form-control').on('change', function (event) {
        $('#null-' + $(this).attr('id')).prop('checked', false);
    });
    $('#agenda-translations form table input.translation').on('change', function (event) {
        $('#old_name,#new_name').val($(this).val());
    });
    // division up/down // TODO make this F code work in Dist
    $('#agenda-products button[name=division-up], #agenda-products button[name=division-down]').on('click', function (event) {
        event.preventDefault();
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'division-switch': $(this).val(),
                'division-delta': $(this).prop('name') == 'division-up' ? -1.5 : 1.5,
                'token': TOKEN
            },
            type: 'POST',
            success: function (data) {
                location.reload();
            }
        });
    });
    // product up/down // TODO make this F code work in Dist
    $('#agenda-products button[name=product-up], #agenda-products button[name=product-down]').on('click', function (event) {
        event.preventDefault();
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'product-switch': $(this).val(),
                'product-delta': $(this).prop('name') == 'product-up' ? -1.5 : 1.5,
                'token': TOKEN
            },
            type: 'POST',
            success: function (data) {
                location.reload();
            }
        });
    });
    //vDivider
    $('#v-divider').on('mousedown', function (event) {
        vDividerMoving = true;
        if (event.buttons & 1) {
            event.preventDefault();
        }
    });
    $('#v-divider').on('mouseup', function (event) {
        $('#v-divider').css('left', vDivider + '%');
        $('#admin-sidebar').css('max-width', vDivider + '%');
        $('#admin-main').css('max-width', 'calc(' + (100 - vDivider) + '% - 15px)').css('flex', '0 0 ' + (100 - vDivider) + '%');
        vDividerMoving = false;
        localStorage.setItem('vDivider', vDivider);
    });
    $('body > div.container-fluid.row').on('mousemove', function (event) {
        if (vDividerMoving) {
            vDivider = event.clientX * 100 / event.delegateTarget.offsetWidth;
            vDivider = Math.max(Math.min(vDivider, 80), 5);
            $('#v-divider').css('left', vDivider + '%').prop('title', vDivider.toFixed(1) + '%');
        }
    });
    vDivider = (localStorage.getItem('vDivider') || vDivider) - 0;
    $('#v-divider').trigger('mouseup', {'buttons': 1, 'preventDefault': function (){}});
    // save content of summernote editor even if in codeView
    $('.note-codable').on('blur', function () {
        $(this).closest('.TableAdminTextarea').find('textarea:first-child').val($(this).val());
    });
    // show/hide table columns
    $('.toggle-div input[type=checkbox]').on('change', function (event) {
        let rand = $(this).closest('.toggle-div').data('rand');
        toggleTableColumn($('#table-admin' + rand), $(this).data('column'), $(this).prop('checked'));
    });
    $('.database .form-control').on('change', function (event) {
        $('#null-' + $(this).attr('id')).prop('checked', false);
    });
    $('#agenda-translations form table input.translation').on('change', function (event) {
        $('#old_name,#new_name').val($(this).val());
    });
    // toggles Export, Edit, Clone buttons based on row selection
    $('.selected-rows .total-rows').on('click', function (event) {
        updateNumberOfSelectedRows(this);
    });
    $('.selected-rows button').on('click', function (event) {
        if ($(this).parent().find('.listed').text() - 0 || $(this).parent().find('input[name=total-rows]').is(':checked')) {
            return true;
        } else {
            event.preventDefault();
            addMediaMessage(TRANSLATE['Select at least one record and try again.']);
            return false;
        }
        
    });
    // #nav-search-button toggles a #nav-search-button and get nav-search-input focused
    $('#nav-search-button').on('click', function (event) {
        $('#nav-search-form').toggle();
        $('#nav-search-input').focus();
    });
    // edit-selected - filling in some value changes operand from "original" to "value"
    $('.table-edit-selected tr td:nth-child(3) input, .table-edit-selected tr td:nth-child(3) textarea').on('change', function (event) {
        let op = $(this).closest('tr').find('td:nth-child(2) select');
        if (op.val() == 'original') {
            op.val('value');
        }
    });
    // edit-selected - changing operand to "original" erases the argument value (or unchecks all checkboxes/radio buttons)
    $('.table-edit-selected tr td:nth-child(2) select').on('change', function (event) {
        if ($(this).val() == 'original') {
            let arg = $(this).closest('td').next().find('input,textarea');
            if (arg[1] && arg[1].nodeName == 'INPUT' && (arg[1].type == 'checkbox' || arg[1].type == 'radio')) {
                arg.parent().find('input[type=checkbox], input[type=radio]').prop('checked', false);
            } else {
                arg.val('');
            }
        }
    });
    // input.mycms-password --> input group with button to toggle
    $('input.mycms-password').each(function () {
        $(this).removeClass('mycms-password');
        $(this)[0].outerHTML = '<div class="input-group">' + $(this)[0].outerHTML + '<div class="input-group-append"><button class="btn btn-secondary mycms-password-toggle" type="button"><i class="fa fa-eye-slash"></i></button></div>';
    });
    $('.mycms-password-toggle').on('click', function (event) {
        event.preventDefault();
        let password = $(this).parent().parent().find('input.form-control');
        $(password).attr('type', password.prop('type') == 'password' ? 'text' : 'password');
        $(this).find('i.fa').removeClass('fa-eye').removeClass('fa-eye-slash').addClass(password.prop('type') == 'password' ? 'fa-eye-slash' : 'fa-eye');
    });
    // friendly URLs, fill up an url
    $('form.friendly-urls .input-group .input-group-text.btn').on('click', function () {
        let url = $(this).parent().parent().find('input[type=text]');
        $(url).val($(url).data('fill'));
    });
    // friendly URLs, fill up all empty urls
    $('form.friendly-urls button.btn-fill').on('click', function () {
        let urls = $('form.friendly-urls .input-group input[type=text].form-control');
        for (i in urls) {
            if (($(urls[i]).val() == '' || $('form.friendly-urls input#only-empty').is(':checked')) && $(urls[i]).data('fill')) {
                $(urls[i]).val($(urls[i]).data('fill'));
            }
        }
    });
    $('form.friendly-urls button.btn-check-up').on('click', function () {
        let urls = $('form.friendly-urls input[type=text]');
        $(urls).removeClass('is-invalid');
        let unique = {};
        for (i in urls) {
            if (typeof(unique[urls[i].value]) == 'undefined') {
                unique[urls[i].value] = 1;
            } else {
                $(urls[i]).addClass('is-invalid');
            }
        }
    });

}
