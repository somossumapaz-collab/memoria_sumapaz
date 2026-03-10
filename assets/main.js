/**
 * Main application logic for loading components dynamically.
 */
import { createNavbar } from '../components/navbar.js';
import { createFooter } from '../components/footer.js';

document.addEventListener("DOMContentLoaded", () => {
    // Determine base path for relative links
    const path = window.location.pathname;
    let basePath = "./";

    if (path.includes("/somos-sumapaz/") || path.includes("/memoria-sumapaz/")) {
        basePath = "../";
    }

    // Inject Navbar & Footer
    const header = document.querySelector("header") || document.body;
    header.prepend(createNavbar(basePath));

    document.body.appendChild(createFooter());
});

