document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('chat-toggle-btn');
    const closeBtn = document.getElementById('chat-close-btn');
    const chatWindow = document.getElementById('chat-window');
    const messagesContainer = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send-btn');

    let isFirstOpen = true;

    // Toggle Chat Window
    if(toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            chatWindow.classList.toggle('active');
            if (chatWindow.classList.contains('active')) {
                chatInput.focus();
                if (isFirstOpen) {
                    // Mensaje de bienvenida inicial
                    addMessage("¡Hola! Soy el asistente virtual de Somos Sumapaz. ¿En qué te puedo ayudar hoy con nuestros productores locales o productos campesinos?", 'bot');
                    isFirstOpen = false;
                }
            }
        });
    }

    if(closeBtn) {
        closeBtn.addEventListener('click', () => {
            chatWindow.classList.remove('active');
        });
    }

    // Send Message
    const sendMessage = async () => {
        const text = chatInput.value.trim();
        if (!text) return;

        // Limpiar input y agregar mensaje del usuario
        chatInput.value = '';
        addMessage(text, 'user');

        // Mostrar indicador de "escribiendo..."
        const typingId = showTypingIndicator();

        try {
            const response = await fetch('api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });

            const result = await response.json();
            
            // Quitar indicador de escribiendo
            removeTypingIndicator(typingId);

            if (result.error) {
                addMessage("Lo siento, hubo un problema al conectarme: " + result.error, 'bot');
            } else if (result.reply) {
                addMessage(result.reply, 'bot');
            } else {
                addMessage("Lo siento, no pude entender la respuesta.", 'bot');
            }

        } catch (error) {
            removeTypingIndicator(typingId);
            addMessage("Error de conexión. Revisa tu internet.", 'bot');
            console.error("Chat error:", error);
        }
    };

    if(sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }

    if(chatInput) {
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }

    function addMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('chat-message', sender);
        msgDiv.textContent = text;
        messagesContainer.appendChild(msgDiv);
        scrollToBottom();
    }

    function showTypingIndicator() {
        const id = 'typing-' + Date.now();
        const typingDiv = document.createElement('div');
        typingDiv.id = id;
        typingDiv.classList.add('typing-indicator');
        typingDiv.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;
        messagesContainer.appendChild(typingDiv);
        scrollToBottom();
        return id;
    }

    function removeTypingIndicator(id) {
        const indicator = document.getElementById(id);
        if (indicator) {
            indicator.remove();
        }
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
});
