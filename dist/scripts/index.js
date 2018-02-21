// scroll effects
AOS.init()

$(document).ready(function(){
    // carousel
    $('.owl-carousel').owlCarousel({
        items: 1,
        loop: true,
        nav: false,
        autoplay: true,
        autoplayTimeout: 5000
    });

    // menu
    $('.hamburger').click(function(e) {
        $('.header').toggleClass('header--opened')
        $('.menu').show();
    });

    // language menu
    $('#language-menu > a').on('click', function(event){
        event.preventDefault()
        if ($(this).hasClass('disabled')) {
            return false
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
                    location.reload()
                }
            }
        })
    });
    // searchbar
    $('.toggleSearch').click(function(e) {
        e.preventDefault()
        $('.searchbar').toggleClass('visible')

    });

    // hide menu when search field is focused
    $('.searchbar input').focus( function(e) {
        $('.menu').hide();
    } );

    // wizard
    $('.activeTile').click(function(e) {
        $(this).parent().find('.activeTile').removeClass('active')
        $(this).parent().find('.activeTile input:checked').parent().addClass('active')
    });
    $('.activate_slide2').click(function(e) {
        $('.wizard1').removeClass('active')
        $('.wizard2').addClass('active')
        $('html, body').animate({scrollTop: 0}, 0)
    });
    $('.activate_slide3').click( function(e) {
        $('.wizard2').removeClass('active')
        $('.wizard3').addClass('active')
        $('html, body').animate({scrollTop: 0}, 0)
    });
    $('.activate_slide4').click( function(e) {
        $('.wizard3').removeClass('active')
        $('.wizard4').addClass('active')
        $('html, body').animate({scrollTop: 0}, 0)
    });

    // slider - propagate slider value to number box
    $('.wizard-slider input').change( function(e) {
        var value = $('.wizard-slider input').val()
        console.log(value)
        $('.wizard-people input').val(value)
    });
    $('.wizard-people input').change( function(e) {
        var value = $('.wizard-people input').val()
        console.log(value)
        $('.wizard-slider input').val(value)
    });
    
    $('#more-item-switch button').click( function(e) {
        $('#more-item-switch').hide();
    });
    /* @todo placeholder for "click on tile" instead of following code within product description <div class="tile tile5 aos-init" data-aos="fade-up" onclick="$('html, body').animate({scrollTop: $('#projekcni-prace').offset().top-1.5*$('#projekcni-prace').height()},2000);">
    $("#button").click(function () {
        $('html, body').animate({
            scrollTop: $("#elementtoScrollToID").offset().top
        }, 2000);
    });
    */
})
