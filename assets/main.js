import { createFooter } from '../components/footer.js';
import { createPostCard } from '../components/post-card.js';

document.addEventListener("DOMContentLoaded", () => {
    // Current Path for relative links
    const basePath = "./";

    // Sample Posts Data
    const posts = [
        {
            title: "El Alma del Páramo",
            date: "10 Marzo, 2026",
            author: "Redacción Somos Sumapaz",
            excerpt: "Explora la biodiversidad única del Páramo de Sumapaz y por qué es vital para el equilibrio hídrico de la región.",
            image: "assets/post1.png",
            url: "#"
        },
        {
            title: "Sabores de Nuestra Tierra",
            date: "8 Marzo, 2026",
            author: "María Rodríguez",
            excerpt: "Conoce a los productores locales que transforman los frutos de la montaña en delicias artesanales de exportación.",
            image: "assets/post2.png",
            url: "#"
        },
        {
            title: "Historias de Resiliencia",
            date: "5 Marzo, 2026",
            author: "Juan Pérez",
            excerpt: "La comunidad de Sumapaz comparte su visión sobre el futuro del territorio y el legado de paz que están construyendo.",
            image: "assets/post3.png",
            url: "#"
        }
    ];

    // Category Navigation
    document.querySelectorAll(".category-item").forEach(item => {
        item.addEventListener("click", () => {
            const categoryName = item.querySelector(".category-label").textContent.trim();
            window.location.href = `productos.html?categoria=${encodeURIComponent(categoryName)}`;
        });
    });

    // Search Box Navigation
    const searchInput = document.querySelector(".search-bar input");
    if (searchInput) {
        searchInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter" && searchInput.value.trim() !== "") {
                window.location.href = `productos.html?search=${encodeURIComponent(searchInput.value.trim())}`;
            }
        });
    }

    // Render Posts
    const postsContainer = document.getElementById("posts-container");
    if (postsContainer) {
        posts.forEach(post => {
            postsContainer.appendChild(createPostCard(post, basePath));
        });
    }

    // Inject Footer
    document.body.appendChild(createFooter());

    // Fetch and Render Popular Products from API
    fetch("api/get_productos.php")
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById("popular-products-container");
            if (!container) return;

            if (data.error) {
                console.error("API Error:", data.error);
                return;
            }

            if (data.length > 0) {
                container.innerHTML = ""; // Clear loader text
                data.forEach(product => {
                    container.appendChild(createProductCard(product));
                });
            }
        })
        .catch(error => {
            console.error("Fetch Error:", error);
        });
});

/**
 * Creates a product card element for the popular products section
 * @param {Object} product - Product data object
 * @returns {HTMLElement}
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




