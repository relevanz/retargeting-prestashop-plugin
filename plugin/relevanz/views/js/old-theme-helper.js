document.addEventListener("DOMContentLoaded", function(event) {
	try {
        $('[data-toggle="popover"]').popover();
    } catch (e) {
    	console.error(e);
    }

    if (typeof _PS_VERSION_ === 'string') {
        $('body').addClass('ps_v_'+_PS_VERSION_.split('.').slice(0,2).join('_'));
    }
});
