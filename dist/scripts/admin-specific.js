/* global $ */

/**
 * JavaScript client-side of MyCMS admin, additional project-specific code
 * dependent JS: admin.js
 * dependent variables: TOKEN, LISTED_FIELDS, ASSETS_SUBFOLDERS, WHERE_OPS
 */

$(document).ready(function(){
    standardDocumentReady();

    // insert your code below instead of this one

    $('details summary a').on('click', function(event) {
        location.href = $(this).prop('href');
    });
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

    // toggle null checkbox according to select/textarea content
    // TODO do this automatically when the NULL checkbox is visible
    // product category_id - just an example
    $('.database select[name=fields\\[category_id\\]]').on('change', function(event) {
        selectWithNullOnChange(this, 'category_id');
    });
    // content product_id - just an example
    $('.database select[name=fields\\[product_id\\]]').on('change', function(event) {
        selectWithNullOnChange(this, 'product_id');
    });
    $('.database input[name=fields\\[url_en\\]]').on('change keyup paste', function(event) {selectWithNullOnChange(this, 'url_en');});
    $('.database input[name=fields\\[url_cs\\]]').on('change keyup paste', function(event) {selectWithNullOnChange(this, 'url_cs');});
    $('.database input[name=fields\\[url_de\\]]').on('change keyup paste', function(event) {selectWithNullOnChange(this, 'url_de');});
    $('.database input[name=fields\\[url_fr\\]]').on('change keyup paste', function(event) {selectWithNullOnChange(this, 'url_fr');});
    // redirector new_url
    $('.database textarea[name=fields\\[new_url\\]]').on('change keyup paste', function(event) {
        selectWithNullOnChange(this, 'new_url'); // param 2 equals name of the field
    });
    // $('textarea.richtext').summernote
    $('.database textarea[name=fields\\[content_en\\]]').on('summernote.change', function(we, contents) {selectWithNullOnChangeContent(contents, 'content_en');});
    $('.database textarea[name=fields\\[content_cs\\]]').on('summernote.change', function(we, contents) {selectWithNullOnChangeContent(contents, 'content_cs');});
    $('.database textarea[name=fields\\[content_de\\]]').on('summernote.change', function(we, contents) {selectWithNullOnChangeContent(contents, 'content_de');});
    $('.database textarea[name=fields\\[content_fr\\]]').on('summernote.change', function(we, contents) {selectWithNullOnChangeContent(contents, 'content_fr');});

    // toggle buttons on "products" and "pages"
    $('#products-actives').on('click', function(){
        $('#agenda-products .inactive-item').toggle(); //product
        $('#agenda-products h4.inactive').toggle(); //category
    });
    $('#pages-actives').on('click', function(){
        $('#agenda-pages .inactive-item').toggle();
    });
    $('#pages-toggle').on('click', function(){
        $('#agenda-pages details').prop('open', $(this).data('open') ? true : false);
        $(this).data('open', $(this).data('open') ? 0 : 1);
    });
    $('#urls-toggle').on('click', function(){
        $('#agenda-urls details').prop('open', $(this).data('open') ? true : false);
        $(this).data('open', $(this).data('open') ? 0 : 1);
    });
    $('#products-texts').on('click', function(){
        $('#agenda-products details sup.product-texts').toggle();
    });
    $('#products-images').on('click', function(){
        $('#agenda-products details sup.product-images').toggle();
    });
    // category up/down
    $('#agenda-products button.category-switch,#agenda-pages button.category-switch').on('click', function(event) {
        event.preventDefault();
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'category-switch': $(this).val(),
                'id': $(this).data('id'),
                'token': TOKEN
            },
            type: 'POST'
        }).always(function (data) {
            location.reload();
        });
    });
    // product up/down
    $('#agenda-products button.product-switch').on('click', function(event) {
        event.preventDefault();
        $.ajax({
            url: '?keep-token',
            dataType: 'json',
            data: {
                'product-switch': $(this).val(),
                'id': $(this).data('id'),
                'token': TOKEN
            },
            type: 'POST'
        }).always(function (data) {
            location.reload();
        });
    });
});
