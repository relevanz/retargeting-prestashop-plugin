/**
 * @author    Releva GmbH - https://www.releva.nz
 * @copyright 2019-2021 Releva GmbH
 * @license   https://opensource.org/licenses/MIT  MIT License (Expat)
 */

document.addEventListener("DOMContentLoaded", function(event) {
    $('input[type="text"][readonly]').click(function () {
        var t = $(this)[0];
        t.focus();
        t.select();
    });
    $('form .form-group .sr-only').each(function () {
        $(this)
            .parent()
            .find('[data-toggle="popover"]')
            .attr('data-content', $(this).html().replace('"', '&quot;'));
    });
});
