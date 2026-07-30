document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('chat-toggle-btn');
    const closeBtn = document.getElementById('chat-close-btn');
    const chatWindow = document.getElementById('chat-window');
    const messagesContainer = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send-btn');

    let isFirstOpen = true;
    let chatHistory = []; // Historial conversacional en memoria (user y assistant)

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
        
        // Agregar al historial conversacional
        chatHistory.push({ role: 'user', content: text });

        // Mostrar indicador de "escribiendo..."
        const typingId = showTypingIndicator();

        try {
            const response = await fetch('api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    message: text,
                    history: chatHistory.slice(-8) // Enviar los últimos 8 giros de la conversación
                })
            });

            const result = await response.json();
            
            // Quitar indicador de escribiendo
            removeTypingIndicator(typingId);

            if (result.error) {
                addMessage("Lo siento, hubo un problema al conectarme: " + result.error, 'bot');
            } else if (result.reply) {
                addMessage(result.reply, 'bot');
                // Guardar la respuesta del bot en el historial conversacional
                chatHistory.push({ role: 'assistant', content: result.reply });
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
        
        if (sender === 'bot') {
            msgDiv.innerHTML = parseMarkdown(text);
        } else {
            msgDiv.textContent = text;
        }
        
        messagesContainer.appendChild(msgDiv);
        scrollToBottom();
    }

    /**
     * Parser robusto de Markdown a HTML (Tablas, Títulos, Negrilla, Listas, Saltos de línea)
     */
    function parseMarkdown(text) {
        if (!text) return '';

        let html = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // 1. Detección y renderizado de tablas (| col1 | col2 |)
        const lines = html.split('\n');
        let inTable = false;
        let tableHtml = '';
        let processedLines = [];

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i].trim();
            if (line.startsWith('|') && line.endsWith('|')) {
                // Ignorar línea separadora (|---|---|)
                if (/^\|[\s\-:|]+\|$/.test(line)) {
                    continue;
                }
                const cells = line.split('|').slice(1, -1).map(c => c.trim());
                if (!inTable) {
                    inTable = true;
                    tableHtml = '<div class="chat-table-wrapper"><table class="chat-table"><thead><tr>';
                    cells.forEach(c => tableHtml += `<th>${c}</th>`);
                    tableHtml += '</tr></thead><tbody>';
                } else {
                    tableHtml += '<tr>';
                    cells.forEach(c => tableHtml += `<td>${c}</td>`);
                    tableHtml += '</tr>';
                }
            } else {
                if (inTable) {
                    inTable = false;
                    tableHtml += '</tbody></table></div>';
                    processedLines.push(tableHtml);
                    tableHtml = '';
                }
                processedLines.push(line);
            }
        }
        if (inTable) {
            tableHtml += '</tbody></table></div>';
            processedLines.push(tableHtml);
        }

        html = processedLines.join('\n');

        // 2. Encabezados (###, ####)
        html = html.replace(/^#### (.*$)/gim, '<h4 class="chat-h4">$1</h4>');
        html = html.replace(/^### (.*$)/gim, '<h3 class="chat-h3">$1</h3>');
        html = html.replace(/^## (.*$)/gim, '<h2 class="chat-h2">$1</h2>');

        // 3. Negrilla y Cursiva (**texto**, *texto*)
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // 4. Listas con viñetas (- elemento, * elemento)
        html = html.replace(/^\s*[\-\*]\s+(.*$)/gim, '<li class="chat-li">$1</li>');

        // 5. Saltos de línea y formateo de párrafos
        html = html.replace(/\n\n/g, '<br><br>');
        html = html.replace(/\n/g, '<br>');

        // Limpiar saltos sobrantes dentro de elementos HTML renderizados
        html = html.replace(/<div class="chat-table-wrapper"><br>/g, '<div class="chat-table-wrapper">');
        html = html.replace(/<\/table><\/div><br>/g, '</table></div>');
        html = html.replace(/<\/h([234])><br>/g, '</h$1>');

        return html;
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
