/* global $, AOS, FEATURE_FLAGS, ga, TOKEN */

// scroll effects
FEATURE_FLAGS['offline_dev'] || AOS.init();

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

});
