document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('funncoba_country_footer');
    const form = document.getElementById('funncoba_country_form_footer');
    if (select && form) {
        select.addEventListener('change', function() {
            form.submit();
        });
    }
});
