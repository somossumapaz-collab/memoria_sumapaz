/**
 * Navbar Component
 */
export const createNavbar = (basePath = "") => {
    const nav = document.createElement("nav");
    nav.className = "navbar-custom";
    nav.innerHTML = `
        <div class="container">
            <a href="${basePath}index.html">
                <img src="${basePath}assets/logo_somossumapaz.png" alt="Somos Sumapaz" class="logo-img">
            </a>
            <div class="search-container">
                <span class="search-label">Busca el producto que necesitas</span>
                <div class="search-bar">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Buscar...">
                </div>
            </div>
        </div>
    `;
    return nav;
};

