<?php
/**
 * Common Scripts Component
 * Include this before closing </body> tag
 */
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Side navigation functionality
    const sideNavToggle = document.querySelector('.menu-toggle');
    const sideNav = document.querySelector('.side-nav');
    const sideNavBackdrop = document.querySelector('.side-nav-backdrop');
    const sideNavClose = document.querySelector('.close-sidenav');

    if (sideNavToggle && sideNav) {
        sideNavToggle.addEventListener('click', () => {
            sideNav.classList.add('open');
            sideNavBackdrop.classList.add('active');
            document.body.classList.add('no-scroll');
        });
    }

    function closeSideNav() {
        if (sideNav) {
            sideNav.classList.remove('open');
            sideNavBackdrop.classList.remove('active');
            document.body.classList.remove('no-scroll');
        }
    }

    if (sideNavClose) sideNavClose.addEventListener('click', closeSideNav);
    if (sideNavBackdrop) sideNavBackdrop.addEventListener('click', closeSideNav);

    // Back to top button
    const backToTopBtn = document.querySelector('.back-to-top');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Mobile nav toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('nav');
    if (menuToggle && nav) {
        menuToggle.addEventListener('click', () => nav.classList.toggle('active'));
    }

    // Image error handling
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            if (!this.src.includes('placeholder')) {
                const src = this.src;
                const filename = src.split('/').pop().split('.')[0];

                if (src.endsWith('.jpg')) {
                    this.src = src.replace('.jpg', '.webp');
                } else if (src.endsWith('.webp')) {
                    this.src = 'images/placeholder.jpg';
                } else {
                    this.src = 'images/placeholder.jpg';
                }
            }
        });
    });
});
</script>
