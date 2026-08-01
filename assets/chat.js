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
     * Parser robusto de Markdown a HTML (Tablas, Títulos, Negrilla, Listas, Párrafos)
     */
    function parseMarkdown(text) {
        if (!text) return '';

        let str = text;

        // Normalizar saltos de línea
        str = str.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

        // Garantizar saltos de línea antes de encabezados (#, ##, ###) cuando vienen pegados en el texto
        str = str.replace(/([^\n])\s*(#{1,4}\s+)/g, '$1\n\n$2');

        // 1. Detección y renderizado de tablas Markdown (| col1 | col2 |)
        const tableRegex = /((?:\|[^\n]+\|\n?)+)/g;
        str = str.replace(tableRegex, (match) => {
            const lines = match.trim().split('\n').filter(l => l.trim());
            if (lines.length < 2) return match;

            let htmlTable = '<div class="chat-table-wrapper"><table class="chat-table">';
            let isHeader = true;

            for (let i = 0; i < lines.length; i++) {
                let line = lines[i].trim();

                // Omitir línea separadora |---|---|
                if (/^\|[\s\-:|]+\|$/.test(line)) {
                    continue;
                }

                const cells = line.split('|').slice(1, -1).map(c => c.trim());

                if (isHeader) {
                    htmlTable += '<thead><tr>';
                    cells.forEach(c => htmlTable += `<th>${parseInlineMarkdown(c)}</th>`);
                    htmlTable += '</tr></thead><tbody>';
                    isHeader = false;
                } else {
                    htmlTable += '<tr>';
                    cells.forEach(c => htmlTable += `<td>${parseInlineMarkdown(c)}</td>`);
                    htmlTable += '</tr>';
                }
            }
            if (!isHeader) {
                htmlTable += '</tbody>';
            }
            htmlTable += '</table></div>';
            return '\n\n' + htmlTable + '\n\n';
        });

        // 2. Encabezados (#, ##, ###, ####)
        str = str.replace(/^#### (.*$)/gim, '<h4 class="chat-h4">$1</h4>');
        str = str.replace(/^### (.*$)/gim, '<h3 class="chat-h3">$1</h3>');
        str = str.replace(/^## (.*$)/gim, '<h2 class="chat-h2">$1</h2>');
        str = str.replace(/^# (.*$)/gim, '<h2 class="chat-h2">$1</h2>');

        // 3. Reglas horizontales (--- o ***)
        str = str.replace(/^[\-\*]{3,}$/gim, '<hr class="chat-hr">');

        // 4. Listas ordenadas y no ordenadas
        str = str.replace(/(?:^\s*[\-\*]\s+.*(?:\n|$))+/gm, (match) => {
            const items = match.trim().split('\n').map(item => {
                const cleanItem = item.replace(/^\s*[\-\*]\s+/, '');
                return `<li class="chat-li">${parseInlineMarkdown(cleanItem)}</li>`;
            }).join('');
            return `<ul class="chat-ul">${items}</ul>`;
        });

        str = str.replace(/(?:^\s*\d+\.\s+.*(?:\n|$))+/gm, (match) => {
            const items = match.trim().split('\n').map(item => {
                const cleanItem = item.replace(/^\s*\d+\.\s+/, '');
                return `<li class="chat-li">${parseInlineMarkdown(cleanItem)}</li>`;
            }).join('');
            return `<ol class="chat-ol">${items}</ol>`;
        });

        // 5. Formatos en línea en el resto del texto
        str = parseInlineMarkdown(str);

        // 6. Formateo de párrafos (convertir bloques de texto en <p>)
        const blocks = str.split(/\n{2,}/);
        const formattedBlocks = blocks.map(block => {
            block = block.trim();
            if (!block) return '';
            if (/^<(div|table|h[1-6]|ul|ol|hr|blockquote)/i.test(block)) {
                return block;
            }
            return `<p class="chat-p">${block.replace(/\n/g, '<br>')}</p>`;
        });

        return formattedBlocks.join('');
    }

    function parseInlineMarkdown(text) {
        if (!text) return '';
        let str = text;
        // Negrilla (**texto**)
        str = str.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Cursiva (*texto*)
        str = str.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Código en línea (`código`)
        str = str.replace(/`(.*?)`/g, '<code class="chat-code">$1</code>');
        return str;
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
