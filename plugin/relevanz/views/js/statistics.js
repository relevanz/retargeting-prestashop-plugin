document.addEventListener("DOMContentLoaded", function(event) {
    let $iframe = $('#relevanz-stats iframe');
    
    if (typeof _PS_VERSION_ === 'string') {
        $('body').addClass('ps_v_'+_PS_VERSION_.split('.').slice(0,2).join('_'));
    }
    
    if ($iframe.length > 0) {
        $('#main').addClass('has-stats-frame');
        $iframe.on('load', function () {
            setTimeout(function () {
                $iframe.removeClass('loading');
            }, 1000);
        });
    }
});
