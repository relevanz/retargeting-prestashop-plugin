document.addEventListener("DOMContentLoaded", function(event) {
    let $iframe = $('#relevanz-stats iframe');

    if ($iframe.length > 0) {
        $('#main').addClass('has-stats-frame');
        $iframe.on('load', function () {
            setTimeout(function () {
                $iframe.removeClass('loading');
            }, 1000);
        });
    }
});
