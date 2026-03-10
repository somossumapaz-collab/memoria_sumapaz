/**
 * Footer Component
 */
export const createFooter = () => {
    const footer = document.createElement("footer");
    footer.innerHTML = `
        <div class="container">
            <p class="serif">© 2026 Memoria Sumapaz</p>
            <p>Historias, territorio y comunidad</p>
        </div>
    `;
    return footer;
};
