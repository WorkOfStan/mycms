/** JavaScript client-side of MyCMS admin
 * prerequisities - libraries: JQuery, variables: TOKEN, LISTED_FIELDS, ASSETS_SUBFOLDERS
 */

WHERE_OPS = ['=', '<', '>', '<=', '>=', 'LIKE', 'REGEXP', 'IN', 'IS NULL', 'BIT AND'];
sortIndex = 0;
searchIndex = 0;
imageSelectorTarget = '';

function prepareDatetimepicker(date, time) {
    timeformat = (date ? 'dd-MM-yyyy' : '') + (date && time ? ' ' : '') + (time ? 'hh:mm:ss' : '');
    $('input.input-' + (date ? 'date' : '') + (time ? 'time' : '')).each(function() {
        div = $('<div class="input-append date">' + $(this).attr('data-format', timeformat)[0].outerHTML 
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

function addSortRow(field, descending)
{
    field = parseInt(field);
    if (field < 0 || field > LISTED_FIELDS.length) {
        return false;
    }
    html = '<select name="sort[' + sortIndex + ']" class="select-sort">\n<option />\n';
    for (i in LISTED_FIELDS) {
        html += '<option value="' + (i - 0 + 1) + '"' + ((i - 0 + 1) == field ? ' selected="selected"' : '') + '>' + LISTED_FIELDS[i] + '</option>\n';
    }
    html += '</select>\n'
        + '<label data-toggle="tooltip" title="' + TRANSLATE['Descending'] +'"><input type="checkbox" name="desc[' + sortIndex + ']"' + (descending ? ' checked="checked"' : '') + ' /> '
        + '<span class="glyphicon glyphicon-sort-by-attributes-alt fa fa-sort-amount-desc" aria-hidden="true"/></label><br />\n';
    $('#sort-div').append(html);
    sortIndex++;
    return true;
}

function addSearchRow(field, op, value)
{
    field = parseInt(field);
    if (field < 0 || field > LISTED_FIELDS.length) {
        return false;
    }
    html = '<select name="col[' + searchIndex + ']" class="select-search">\n<option />\n';
    for (i in LISTED_FIELDS) {
        html += '<option value="' + (i - 0 + 1) + '"' + (field == (i - 0 + 1) ? ' selected="selected"' : '') + '>' + LISTED_FIELDS[i] + '</option>\n';
    }
    html += '</select>\n<select name="op[' + searchIndex + ']" class="select-op">\n';
    for (i in WHERE_OPS) {
        html += '<option value="' + i + '"' + (op == i ? ' selected="selected"' : '') + '>' + WHERE_OPS[i] + '</option>\n';
    }
    html += '</select>\n<input type="search" name="val[' + searchIndex + ']" value="' + value + '" size="8" /><br />\n';
    $('#search-div').append(html);
    searchIndex++;
    return true;
}

function urlChange(changes) {
    pairs = location.search.substr(1).split('&');
    tmp = {};
    for (i in pairs) {
        pair = pairs[i].split('=');
        if (Object.keys(changes).indexOf(pair[0]) == -1) {
            tmp[pair[0]] = pair[1];
        } else if (typeof(changes[pair[0]]) != 'undefined') {
            tmp[pair[0]] = pair[1];
        }
    }
    for (i in changes) {
        if (typeof(changes[i]) != 'undefined') {
            tmp[i] = changes[i];
        }
    }
    result = '';
    for (i in tmp) {
        result += '&' + i + '=' + tmp[i];
    }
    return '?' + result.substr(1);
}

function getAgenda(agenda, options) {
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

function fillAgenda(data, options) {
    html = prefill = '';
    for (i in data.data) {
        html += agendaRow(data, i, options);
    }
    agenda = $('#agenda-' + data.agenda);
    if (typeof(options.prefill) == 'object') {
        for (i in options.prefill) {
            prefill += '&amp;prefill[' + i + ']=' + options.prefill[i];
        }
    }
    agenda.html(html + '<div class="m-1"><a href="?table=' + TAB_PREFIX + options.table + '&amp;where[]=' + prefill + '" class="pl-1"><i class="fa fa-plus-square-o" aria-hidden="true"></i></a> &nbsp; ' + TRANSLATE['New record'] + '</div>');
}

function agendaRow(data, index, options) {
    row = data.data[index];
    result = '<div class="m-1" data-id="' + row.id + '" data-table="' + data.agenda + '">\n'
        + '<a href="?table=' + TAB_PREFIX + (options['table'] || data.agenda) + '&amp;where[id]=' + row.id + '"'
        + ' class="btn btn-link btn-xs" title="edit"><i class="fa fa-pencil" aria-hidden="true"></i></a>\n';
    if (row.sort) {
        result += '<button data-dir="-1" class="btn btn-secondary btn-xs btn-sort" title="move up" onclick="sortButtonOnClick(this)"' + (index == 0 ? ' disabled' : '') + '><i class="fa fa-long-arrow-up" aria-hidden="true"></i></button>\n'
            + '<button data-dir="1" class="btn btn-secondary btn-xs btn-sort" title="move down" onclick="sortButtonOnClick(this)"' + (index == data.data.length - 1 ? ' disabled' : '') + '><i class="fa fa-long-arrow-down" aria-hidden="true"></i></button>\n'
    }
    if (data.subagenda) {
        result += '<a href="#" class="btn btn-xs btn-link btn-expand" data-toggle="collapse" data-target="#agenda-' + data.agenda + '-' + row.id + '" aria-expanded="false" aria-controls="agenda-' + data.agenda + '-' + row.id + '" title="expand"><i class="fa fa-caret-down" aria-hidden="true"></i></a>\n';
    }
    result += '<span class="item-name">' + row['name'] + '</span>';
    if (row.join && data.subagenda) {
        result += '<div class="ml-3 my-1 border rounded p-1 alert-secondary subagenda collapse" id="agenda-' + data.agenda + '-' + row.id + '" data-id="' + row.id + '">\n';
        if (typeof(row.join[0]) == "undefined") {
            row.join = [row.join];
        }
        for (j in row.join) {
            result += '<div data-id="' + row.join[j].id + '" data-table="' + data.subagenda + '">'
                + '<a href="?table=' + TAB_PREFIX + data.subagenda + '&amp;where[id]=' + row.join[j]['id'] + '" class="btn btn-link btn-xs" title="edit"><i class="fa fa-pencil" aria-hidden="true"></i></a>\n';
            if (row.join.sort && row.join.length > 1) {
                result += '<button data-dir="-1" class="btn btn-secondary btn-xs btn-sort" title="move up" onclick="sortButtonOnClick(this)"' + (j == 0 ? ' disabled' : '') + '><i class="fa fa-long-arrow-up" aria-hidden="true"></i></button>\n'
                    + '<button data-dir="1" class="btn btn-secondary btn-xs btn-sort" title="move down" onclick="sortButtonOnClick(this)"' + (j == row.join.length - 1 ? ' disabled' : '') + '><i class="fa fa-long-arrow-down" aria-hidden="true"></i></button>\n';
            }
            result += '<span class="item-name">' + row.join[j]['name'] + '</span></div>';
        }
        result += '</div>\n';
    }
    return result + '</div>\n';
}

function updateImageSelector(ImageFolder, ImageFiles) {
    $(ImageFiles).html('<img src="images/loader.gif" />');
    $.ajax({
        url: '?keep-token',
        dataType: 'json',
        data: {
            'subfolder': $(ImageFolder).val(),
            'media-files': 1,
            'token': TOKEN,
            'wildcard': '*.{jpg,gif,png}'
        },
        type: 'POST',
        success: function (data) {
            if (data.success) {
                html = '';
                for (i in data.data) {
                    filename = data.data[i]['name'] + data.data[i]['extension'];
                    src = data.subfolder + '/' + filename;
                    html += '<a href="' + src + '" title="' + filename + '">'
                        + '<img src="' + src + '" data-src="' + src + '" />\n'
                        + '<span>' + filename + '</span></a>\n';
                }
                $(ImageFiles).html(html);
            }
            $(ImageFiles).find('a').on('click', function(event) {
                event.preventDefault();
                tmp = $(ImageFolder).parent().parent(); 
                tmp.find('.note-image-url').val($(this).find('img').data('src'));
                tmp.parent().parent().find('.modal-footer button.btn-primary').click();
            });
        }
    });
}

function fillAssetsSubfolders(element) {
    $(element).append($('<option>', {value: '', text: DIR_ASSETS}));
    for (i in ASSETS_SUBFOLDERS) {
        $(element).append($('<option>', {
            value: ASSETS_SUBFOLDERS[i], 
            text: DIR_ASSETS + ASSETS_SUBFOLDERS[i]
        }));
    }
}

function jsonExpandedTableAddRow(table) {
    html = '<tr><td class="first w-25"><input class="form-control form-control-sm" type="text" name="' + EXPAND_INFIX + 'context[]" onblur="jsonExpandedOnBlur(this)" placeholder="' + TRANSLATE['variable'] + '"></td>'
        + '<td class="second w-75"><input class="form-control form-control-sm" type="text" name="' + EXPAND_INFIX + EXPAND_INFIX + 'context[]" onblur="jsonExpandedOnBlur(this)" placeholder="' + TRANSLATE['value'] + '"></td></tr>';
    $(html).appendTo(table);
}

function jsonExpandedOnBlur(element) {
    tr = $(element).parent().parent();
    first = tr.find('.first input');
    second = tr.find('.second input');
    lastRow = tr.index() + 1 == tr.parent().find('tr').length;
    if (first.val() == '' && second.val() == '') {
        if (!lastRow) {
            tr.remove();
        }
    } else if (lastRow) {
        jsonExpandedTableAddRow(tr.parent());
    }
}

function selectWithNullOnChange(element, name) {
    $('.database input[name=fields-null\\[' + name + '\\]]').prop('checked', !$(element).val());
}

function pad0(input, len) {
    return '0'.repeat(len - String(input).length) + input;
}

function moveCategory(element, up) {
    prefix = $(element).data('prefix');
    siblings = $(element).parent().find('details[data-prefix='+prefix+']');
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

$(document).ready(function(){
    agendas = localStorage.getItem("agendas"); //array of elements that were expanded
    if (agendas) {
        agendas = agendas.split(' ');
    } else {
        agendas=[];
    }
    
    $('[data-toggle="tooltip"]').tooltip();
    $('input[data-order]').on('click', function(event) {
        checkboxOrder = $(this).closest('table').data('order');
        if (event.shiftKey) {
            if (checkboxOrder != null) {
                b = $(this).data('order');
                checked = $('input[data-order=' + checkboxOrder + ']').prop('checked');
                $.each($(this).closest('table').find('input[data-order]'), function(key, value) {
                    o = $(this).data('order');
                    checkboxOrder = $(this).closest('table').data('order');
                    if (o >= Math.min(b, checkboxOrder) && o <= Math.max(b, checkboxOrder)) {
                        $(this).prop('checked', checked);
                    }
                });
            }
        }
        $(this).closest('table').data('order', $(this).data('order'));
    });
    $('#go-to-page').on('click', function() {
        page = prompt('Stránka:');
        if (!isNaN(page) && page > 0 && page < $(this).data('pages')) {
            console.log('@todo go to page: ' + page);//...
        }
    });
    
    prepareDatetimepicker(false, true);
    //summernote
    $('textarea.richtext').summernote({
        height: 300,
        minHeight: null,
        maxHeight: null,
        focus: true,
        lang: 'en-US',
        placeholder: 'Edit...',
        tabsize: 8,
        toolbar: [
            ['Style', ['style', /*'fontname', 'fontsize', 'color',*/ 'clear']],
            ['Text', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript']],
            ['Paragraph', ['ol', 'ul', 'paragraph', 'height']],
            ['Insert', ['picture', 'link', 'video', 'table', 'hr']],
            ['Misc', ['undo', 'redo', 'codeview', 'fullscreen', 'help']],
        ],
        callbacks : {
            onInit : function() {
                var myBtn = '<button id="mySummernoteTool" type="button" class="btn btn-default btn-sm btn-small" title="Custom button" data-event="something" tabindex="-1"><i class="fa fa-wrench"></i></button>';            
                var btnGroup = '<div class="note-Misc btn-group">' + myBtn + '</div>';
                $(btnGroup).appendTo($('.note-toolbar'));
                $('#mySummernoteTool').tooltip({container: 'body', placement: 'bottom'}); // Button tooltips
                $('#mySummernoteTool').click(function(event) { // Button events
                    // insert code
                });
            }
        }
    });
    
    // media - show files on subfolder change
    $('#subfolder').on('change',
        function() {
            $.ajax({
                url: '?keep-token',
                dataType: 'json',
                data: {
                    'subfolder': $(this).val(),
                    'media-files': 1,
                    'token': TOKEN
                },
                type: 'POST',
                success: function (data) {
                    if (data.success) {
                        path = $('#subfolder option:first-child').text() + $('#subfolder').val() + '/';
                        html = '';
                        for (i in data.data) {
                            filename = data.data[i]['name'] + data.data[i]['extension'];
                            src = data.subfolder + '/' + filename;
                            html += '<tr><td><input type="radio" name="file" value="' + filename + '"> <input type="checkbox" name="files[]" value="' + filename + '" id="subfolder-file-' + i + '"></td>'
                                + '<td><a href="' + path + filename + '" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a></td>'
                                + '<td><tt><label for="subfolder-file-' + i + '">' + data.data[i]['name'] + '</label></tt></td>'
                                + '<td><tt>' + data.data[i]['extension'] + '</tt></td>'
                                + '<td class="text-right pl-2"><tt>' + data.data[i]['size'] + '</tt></td>'
                                + '<td class="pl-2"><tt>' + data.data[i]['modified'] + '</tt></td></tr>\n';
                        }
                        $('#media-files').html(html ? '<table class="subfolder-files"><thead>'
                            + '<tr><th><input type="radio" name="file"> <input type="checkbox" class="check-all"/></th><th />'
                            + '<th colspan="2">' + TRANSLATE['name'] + '</th>'
                            + '<th class="text-right">' + TRANSLATE['size'] + '</th>'
                            + '<th class="text-right">' + TRANSLATE['modified'] + '</th></tr></thead>' 
                            + html + '</table>' 
                            : '<i>' + TRANSLATE['No files'] + '</i>'
                        );
                        $('#delete-media-files').toggle(data.data.length > 0);
                        $('table.subfolder-files thead input[type=checkbox].check-all').on('change', function() { // "check all" checkbox
                            $(this).closest('table').find('input[type=checkbox]').prop('checked', $(this).prop('checked'));
                        });
                    }
                }
            });
        }
    );
    $('#delete-media-files').on('click', function(event){
        files = [];
        $.each($('#media-files > table.subfolder-files input[type=checkbox]:checked'), function (index, value) {
            files.push($(value).val());
        });
        if (files.length == 0) {
            alert(TRANSLATE['Select at least one file and try again.']);
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
                }
            }
        });
    });
    //sha1 password before login
    if (typeof($.sha1) == 'function') {
        $('#login-form').on('submit', function() {
            $('#login-password').val($.sha1($('#login-password').val()));
            return true;
        });
        $('#change-password-form').on('submit', function() {
            if (!$('#old-password').val() || !$('#new-password').val() || !$('#retype-password').val()) {
                alert(TRANSLATE['Please, fill necessary data.']);
                return false;
            }
            if ($('#new-password').val() != $('#retype-password').val()) {
                alert(TRANSLATE["Passwords don't match!"]);
                return false;
            }
            $('#old-password').val($.sha1($('#old-password').val()));
            $('#new-password').val($.sha1($('#new-password').val()));
            $('#retype-password').val($.sha1($('#retype-password').val()));
            return true;
        });
        $('.create-user-form').on('submit', function() {
            if (!$('#create-user').val() || !$('#create-password').val() || !$('#create-retype-password').val()) {
                alert(TRANSLATE['Please, fill necessary data.']);
                return false;
            }
            if ($('#create-password').val() != $('#create-retype-password').val()) {
                alert(TRANSLATE["Passwords don't match!"]);
                return false;
            }
            $('#create-password').val($.sha1($('#create-password').val()));
            $('#create-retype-password').val($.sha1($('#create-retype-password').val()));
            return true;
        });
    }
    $.each($('#category-hierarchy details span'), function (index, value){
        summary = $(value).parent().find('> summary');
        $(summary).html($(summary).html() + ' <small>('+($(value).find('> details').length)+')</small>'); 
    });
    $('#category-hierarchy details a.up').on('click', function(event){
        event.preventDefault();
        moveCategory($(this).parent().parent(), true);
    });
    $('#category-hierarchy details a.down').on('click', function(event){
        event.preventDefault();
        moveCategory($(this).parent().parent(), false);
    });
    $('.database textarea[data-maxlength]').on('keyup', function(event){
        $(this).toggleClass('is-invalid', (len = String($(this).val()).length) > (maxlen = $(this).data('maxlength')));
        limit = $(this).nextAll().filter('i.input-limit');
        if (typeof(limit) != "undefined" && typeof(limit[0]) != "undefined") {
            limit[0].title = len + '/' + maxlen;
        } 
    });

    $('table.table-admin thead input[type=checkbox].check-all').on('change', function() { // "check all" checkbox
        $(this).closest('table').find('input[type=checkbox]').prop('checked', $(this).prop('checked'));
    });
    $('#agendas > div > details > summary').on('click', function() {
        parent = $(this).parent();
        index = agendas.indexOf(parent.attr('id'));
        if (parent.prop('open')) {
            if (index != -1) {
                agendas.splice(index, 1);
                localStorage.setItem('agendas', agendas.join(' '));
            }
        } else {
            if (index == -1) {
                agendas.push(parent.attr('id'));
                localStorage.setItem('agendas', agendas.join(' '));
            }
        }
    });
    for (i in agendas) {
        if (agendas[i].substr(0, 8) == 'details-') {
            $('#' + agendas[i]).attr('open', 'open');
        } else {
            $('#' + agendas[i]).show();
        }
    }
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
    prefix = ['summernote', 'modal'];
    for (j in prefix) {
        fillAssetsSubfolders($('#' + prefix[j] + 'ImageFolder'));
    }
    $('#subfolder').change();
    $('button.ImageSelector').on('click', function(event){
        event.preventDefault();
        imageSelectorTarget = $(this).data('target');
        $('#image-selector').modal();
    });
    $('#modalInsertImage').on('click', function(event){
        $(imageSelectorTarget).val($('#modalImagePath').val());
        $('#image-selector').modal('hide');
    });
    $('div.modal[data-type]').on('shown.bs.modal', function (event) {
        if ($(this).find('#summernoteImageFolder') && $(this).find('#summernoteImageFolder option').length == 0) {
            fillAssetsSubfolders($(this).find('#summernoteImageFolder'));
        }
    });
    $('.json-expanded td.first input, .json-expanded td.second input').on('blur', function(event) {jsonExpandedOnBlur(this);});
    $('.database select[name=fields\\[category_id\\]]').on('change', function(event) {selectWithNullOnChange(this, 'category_id');});
    $('.database select[name=fields\\[product_id\\]]').on('change', function(event) {selectWithNullOnChange(this, 'product_id');});
    $('.json-reset').on('click', function(event){
        event.preventDefault();
        field = $(this).data('field');
        $(this).parent().find('textarea[name=fields\\['+field+'\\]]').replaceWith(table = $('<table class="w-100 json-expanded" data-field="' + field + '"></table>'));
        jsonExpandedTableAddRow(table);
        $(this).replaceWith('');
        $(table).find('td:first input').focus();
    });
    $('.btn-fill-now').on('click', function(event){
        event.preventDefault();
        d = new Date();
        now = d.getFullYear() + '-' + pad0(d.getMonth() + 1, 2) + '-' + pad0(d.getDate(), 2) + 'T' + pad0(d.getHours(), 2) + ':' + pad0(d.getMinutes(), 2) + ':' + pad0(d.getSeconds(), 2);   
        $(this).parent().parent().find('input').val(now);
    });
    $('.btn-id-unlock').on('click', function(event){
        event.preventDefault();
        input = $(this).parent().parent().find('input');
        input.prop('readonly', input.prop('readonly') ? false : 'readonly');
    });
    $('details summary a').on('click', function(event){
        location.href = $(this).prop('href');
    });
    // save content of summernote editor even if in codeView
    $('.note-codable').on('blur', function(){
        $(this).closest('.note-editor').siblings('textarea').val($(this).val());
    });
});
