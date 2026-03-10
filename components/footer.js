/**
 * Footer Component
 */
export const createFooter = () => {
    const footer = document.createElement("footer");
    footer.className = "main-footer";
    footer.innerHTML = `
        <div class="container">
            <p>Historias, territorio y comunidad - Somos Sumapaz</p>
        </div>
    `;
    return footer;
};
