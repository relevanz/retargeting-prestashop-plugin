document.addEventListener("DOMContentLoaded", function(event) {
    $('input[type="text"][readonly]').click(function () {
        var t = $(this)[0];
        t.focus();
        t.select();
    });
});
