import { createFooter } from '../components/footer.js';

document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    const categoryId = params.get('id_categoria');
    const categoria = params.get('categoria');
    const search = params.get('search');

    const resultsTitle = document.getElementById("results-title");
    const container = document.getElementById("filtered-products-container");

    // Map IDs to Names for Title
    const categoryNames = {
        "1": "Agricultura y Cultivos",
        "2": "Lácteos y Derivados",
        "3": "Carnes y Proteínas",
        "4": "Servicios Técnicos y Mecánicos",
        "5": "Transporte y Logística Rural",
        "6": "Servicios Comunitarios"
    };

    // Update Title
    if (categoryId && categoryNames[categoryId]) {
        resultsTitle.textContent = `Categoría: ${categoryNames[categoryId]}`;
    } else if (categoria) {
        resultsTitle.textContent = `Categoría: ${categoria}`;
    } else if (search) {
        resultsTitle.textContent = `Buscando: "${search}"`;
    } else {
        resultsTitle.textContent = "Todos los Productos";
    }

    // Handle Search Bar in results page
    const searchInput = document.getElementById("main-search-input");
    const suggestionsBox = document.getElementById("search-suggestions");

    if (searchInput && suggestionsBox) {
        searchInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter" && searchInput.value.trim() !== "") {
                window.location.href = `productos.html?search=${encodeURIComponent(searchInput.value.trim())}`;
            }
        });

        searchInput.addEventListener("input", () => {
            const query = searchInput.value.trim();
            if (query.length < 2) {
                suggestionsBox.style.display = "none";
                return;
            }

            fetch(`api/get_sugerencias.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        suggestionsBox.innerHTML = data.map(s => `<div class="suggestion-item">${s}</div>`).join('');
                        suggestionsBox.style.display = "block";

                        document.querySelectorAll(".suggestion-item").forEach(item => {
                            item.addEventListener("click", () => {
                                searchInput.value = item.textContent;
                                suggestionsBox.style.display = "none";
                                window.location.href = `productos.html?search=${encodeURIComponent(item.textContent)}`;
                            });
                        });
                    } else {
                        suggestionsBox.style.display = "none";
                    }
                });
        });

        // Close suggestions on click outside
        document.addEventListener("click", (e) => {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = "none";
            }
        });
    }


    // Fetch Filtered Data
    let apiUrl = "api/get_productos_filtrados.php?";
    if (categoryId) apiUrl += `id_categoria=${categoryId}`;
    else if (categoria) apiUrl += `categoria=${encodeURIComponent(categoria)}`;
    else if (search) apiUrl += `search=${encodeURIComponent(search)}`;

    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (!container) return;

            if (data.error) {
                container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: red;">Error: ${data.error}</p>`;
                return;
            }

            if (data.length === 0) {
                container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted);">No se encontraron productos para esta búsqueda.</p>`;
            } else {
                container.innerHTML = "";
                data.forEach(product => {
                    container.appendChild(createProductCard(product));
                });
            }
        })
        .catch(error => {
            if (container) {
                container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: red;">Error de conexión.</p>`;
            }
            console.error("Fetch Error:", error);
        });

    // Inject Footer
    document.body.appendChild(createFooter());
});

/**
 * Creates a product card element (Reused logic)
 */
function createProductCard(product) {
    const card = document.createElement("div");
    card.className = "popular-product-card";

    card.innerHTML = `
        <div class="product-info">
            <span class="product-category">${product.categoria}</span>
            <h3 class="product-title">${product.producto}</h3>
            <p class="product-description">${product.descripcion}</p>
        </div>
        <div class="product-action">
            <button class="btn-contact">Contactar</button>
        </div>
    `;

    return card;
}
