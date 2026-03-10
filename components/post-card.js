/**
 * Post Card Component
 */
export const createPostCard = (post, basePath = "") => {
    const card = document.createElement("div");
    card.className = "post-card";
    card.innerHTML = `
        <img src="${basePath}${post.image}" alt="${post.title}">
        <div class="post-info">
            <div class="post-meta">${post.date} • ${post.author}</div>
            <h3 class="card-title">${post.title}</h3>
            <p class="card-desc">${post.excerpt}</p>
            <a href="${basePath}${post.url}" class="btn">Leer más</a>
        </div>
    `;
    return card;
};
