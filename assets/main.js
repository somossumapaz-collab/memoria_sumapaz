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

    // Render Posts
    const postsContainer = document.getElementById("posts-container");
    if (postsContainer) {
        posts.forEach(post => {
            postsContainer.appendChild(createPostCard(post, basePath));
        });
    }

    // Wrap footer injection to ensure it doesn't conflict
    document.body.appendChild(createFooter());
});



