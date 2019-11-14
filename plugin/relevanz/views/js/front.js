document.addEventListener("DOMContentLoaded", function(event) {
    var d = document, jid = 'relevanz-js', js = null;

    if (d.getElementById(jid)
        || (typeof relevanz_tr.url !== 'string')
        || (typeof relevanz_tr.params !== 'string')
    ) {
        return;
    }
    js = d.createElement('script');
    js.id = jid;
    js.async = true;
    js.type = 'text/javascript';
    js.src = relevanz_tr.url+'?'+relevanz_tr.params;
    document.body.insertAdjacentElement('beforeend', js);
});
