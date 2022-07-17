/* global $, AOS, API_BASE, FEATURE_FLAGS, ga, TOKEN */

/**
 * JavaScript client-side of MyCMS webpages
 * (Last MyCMS/dist revision: 2022-07-17, v0.4.7)
 */

// scroll effects
FEATURE_FLAGS['offline_dev'] || AOS.init();

/**
 * API_BASE_DIR is path to which just a noun can be added to address the API
 * @type String
 */
let API_BASE_DIR = API_BASE + 'api/';

$(document).ready(function () {
    // carousel
    $('.owl-carousel').owlCarousel({
        items: 1,
        loop: true,
        nav: false,
        autoplay: true,
        autoplayTimeout: 5000
    });

    // menu
    $('.hamburger').click(function () {
        $('.header').toggleClass('header--opened');
    });

    // language menu
    $('#language-menu > a').on('click', function (event) {
        event.preventDefault();
        if ($(this).hasClass('disabled')) {
            return false;
        }
        $.ajax({
            url: '?keep-token&language=' + $(this).data('value'),
            dataType: 'json',
            data: {
                'language': $(this).data('value'),
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

    // header background
    $(window).scroll(() => {
        if ($(window).scrollTop() > 50) {
            $('.header').addClass('header--background');
        } else {
            $('.header').removeClass('header--background');
        }
    });

    // searchbar
    $('.toggleSearch').click(() => {
        $('.searchbar').toggleClass('searchbar--visible');
        $('.searchbar input').focus(); //TODO: focus only if made visible
        // TODO: test trigger Google Analytics event on search open
        ga('send', 'event', 'search', 'toggle');
    });

    // item creation - TODO CHANGE THIS EXAMPLE CODE TO A LIVE DEMONSTRATION
    $('form[name="form-instance"] [type=button]').on('click', function () {
        //if (!$('input[name="name"]').val()) {
        //    alert('Nová položka musí mít jméno.');
        //    return false;
        //}
        // init parameters
        let url = API_BASE_DIR + 'instance?keep-token';
        let event_id = ($('select[name="event_id"]').length) ? $('select[name="event_id"]').val() : $('input[name="event_id"]').val();
        //if (event_id === '') {
        //    event_id = 1; // the default event id
        //}
        let data = {
            'id': event_id,
            'quantity': $('input[name="quantity"]').val(),
            'created': $('input[name="created"]').val(),
            'token': TOKEN
        };

        // launch request
        ajaxPostRequest(url, data);
    });

    /**
     * Wrapper for ajax call
     * @param {string} url
     * @param {object} data
     * @returns {void}
     */
    function ajaxPostRequest(url, data)
    {
        $.ajax({
            url: url,
            dataType: 'json',
            data: data,
            type: 'POST',
            success: function (data) {
                if (data.success) {
                    // console.log(data); // debug
                    location.reload();
                }
            }
        });
    }

});
