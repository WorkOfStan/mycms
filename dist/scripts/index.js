$(document).ready(function(){
    // language menu
    $('#language-menu > a').on('click', function(event) {
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
            success: function(data) {
                if (data.success) {
                    location.reload();
                }
            }
        });
    });
    // searchbar
    $('.toggleSearch').click(function(e) {
        e.preventDefault();
        $('.searchbar').toggleClass('visible');

    });
    // hide menu when search field is focused
    $('.searchbar input').focus(function(e) {
        $('.menu').hide();
    });
});
