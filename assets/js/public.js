document.addEventListener("DOMContentLoaded", function () {

    /* ---------------------------
       AUTO SUBMIT ON COUNTRY CHANGE
    ---------------------------- */
    const select = document.getElementById('funncoba_country_footer');
    const form   = document.getElementById('funncoba_country_form_footer');

    if (select && form) {
        select.addEventListener('change', () => form.submit());
    }


    /* ---------------------------
       SHOW FOOTER BAR ON SCROLL
    ---------------------------- */
    const footerBar = document.querySelector(".funncoba-footer-bar");
    if (!footerBar) return;

    const showAfter = 300; // px scroll before showing
    let ticking = false;

    function toggleFooterBar() {
        if (window.scrollY > showAfter) {
            footerBar.classList.add("visible");
        } else {
            footerBar.classList.remove("visible");
        }
    }

    window.addEventListener("scroll", function () {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                toggleFooterBar();
                ticking = false;
            });
            ticking = true;
        }
    });

});