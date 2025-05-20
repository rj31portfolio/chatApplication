/**
 * AI Chatbot Widget
 * 
 * This file contains the client-side code for the embeddable chat widget.
 */

(function(window) {
    'use strict';
    
    // Base URL for API requests - will be set based on script location
    let apiBaseUrl = '';
    
    // Store the API key
    let apiKey = '';
    
    // Session ID for this chat
    let sessionId = '';
    
    // Widget DOM elements
    let widgetContainer, chatButton, chatWindow, chatMessages, chatInput, chatSend, chatTyping;
    
    // Widget state
    let isWidgetOpen = false;
    let isTyping = false;
    
    /**
     * Initialize the chat widget
     * @param {string} key - API key for the business
     */
    function init(key) {
        if (!key) {
            console.error('AI Chat Widget: API key is required');
            return;
        }
        
        apiKey = key;
        
        // Determine API base URL from the script location
        const scriptElement = document.getElementById('aiChat');
        if (scriptElement) {
            // Extract base path from script src
            const scriptSrc = scriptElement.src;
            const basePath = scriptSrc.substring(0, scriptSrc.lastIndexOf('/assets/'));
            apiBaseUrl = `${basePath}/api`;
        } else {
            console.error('AI Chat Widget: Cannot determine API base URL');
            return;
        }
        
        // Load widget CSS
        loadStylesheet(`${apiBaseUrl.replace('/api', '')}/assets/css/widget.css`);
        
        // Load Font Awesome for icons
        loadStylesheet('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
        
        // Create session ID
        sessionId = generateSessionId();
        
        // Create widget DOM elements
        createWidgetElements();
        
        // Attach event listeners
        attachEventListeners();
    }
    
    /**
     * Load a stylesheet
     * @param {string} url - URL of the stylesheet
     */
    function loadStylesheet(url) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = url;
        document.head.appendChild(link);
    }
    
    /**
     * Generate a unique session ID
     * @return {string} Session ID
     */
    function generateSessionId() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    
    /**
     * Create the widget DOM elements
     */
    function createWidgetElements() {
        // Create widget container
        widgetContainer = document.createElement('div');
        widgetContainer.id = 'ai-chat-widget';
        
        // Create chat button
        chatButton = document.createElement('div');
        chatButton.id = 'ai-chat-button';
        chatButton.innerHTML = '<i class="fas fa-comments"></i>';
        
        // Create chat window
        chatWindow = document.createElement('div');
        chatWindow.id = 'ai-chat-window';
        
        // Create chat header
        const chatHeader = document.createElement('div');
        chatHeader.id = 'ai-chat-header';
        chatHeader.innerHTML = `
            <h3>Chat Support</h3>
            <button id="ai-chat-close"><i class="fas fa-times"></i></button>
        `;
        
        // Create chat messages area
        chatMessages = document.createElement('div');
        chatMessages.id = 'ai-chat-messages';
        
        // Create typing indicator
        chatTyping = document.createElement('div');
        chatTyping.className = 'ai-chat-typing';
        chatTyping.innerHTML = `
            <div class="ai-chat-message-content">
                <div class="ai-chat-dot"></div>
                <div class="ai-chat-dot"></div>
                <div class="ai-chat-dot"></div>
            </div>
        `;
        chatMessages.appendChild(chatTyping);
        
        // Create chat input area
        const chatInputArea = document.createElement('div');
        chatInputArea.id = 'ai-chat-input-area';
        
        chatInput = document.createElement('input');
        chatInput.id = 'ai-chat-input';
        chatInput.type = 'text';
        chatInput.placeholder = 'Type your message...';
        
        chatSend = document.createElement('button');
        chatSend.id = 'ai-chat-send';
        chatSend.innerHTML = '<i class="fas fa-paper-plane"></i>';
        
        chatInputArea.appendChild(chatInput);
        chatInputArea.appendChild(chatSend);
        
        // Create branding footer
        const chatBranding = document.createElement('div');
        chatBranding.id = 'ai-chat-branding';
        chatBranding.innerHTML = 'Powered by <a href="#" target="_blank">AI Chatbot</a>';
        
        // Assemble the chat window
        chatWindow.appendChild(chatHeader);
        chatWindow.appendChild(chatMessages);
        chatWindow.appendChild(chatInputArea);
        chatWindow.appendChild(chatBranding);
        
        // Add everything to the container
        widgetContainer.appendChild(chatButton);
        widgetContainer.appendChild(chatWindow);
        
        // Add the widget to the page
        document.body.appendChild(widgetContainer);
    }
    
    /**
     * Attach event listeners to widget elements
     */
    function attachEventListeners() {
        // Open/close chat window when button is clicked
        chatButton.addEventListener('click', toggleChatWindow);
        
        // Close chat window when close button is clicked
        document.getElementById('ai-chat-close').addEventListener('click', closeChatWindow);
        
        // Send message when send button is clicked
        chatSend.addEventListener('click', sendMessage);
        
        // Send message when Enter key is pressed in input field
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Add greeting message when chat is first opened
        const isFirstOpen = !localStorage.getItem('ai-chat-opened');
        if (isFirstOpen) {
            setTimeout(function() {
                addBotMessage('Hello! How can I help you today?');
                localStorage.setItem('ai-chat-opened', 'true');
            }, 500);
        }
    }
    
    /**
     * Toggle the chat window open/closed
     */
    function toggleChatWindow() {
        if (isWidgetOpen) {
            closeChatWindow();
        } else {
            openChatWindow();
        }
    }
    
    /**
     * Open the chat window
     */
    function openChatWindow() {
        chatWindow.classList.add('active');
        isWidgetOpen = true;
        
        // Focus the input field
        setTimeout(() => {
            chatInput.focus();
        }, 300);
        
        // If no messages yet, send a greeting
        if (chatMessages.children.length <= 1) { // Only the typing indicator is present
            setTimeout(function() {
                addBotMessage('Hello! How can I help you today?');
            }, 500);
        }
    }
    
    /**
     * Close the chat window
     */
    function closeChatWindow() {
        chatWindow.classList.remove('active');
        isWidgetOpen = false;
    }
    
    /**
     * Send a user message
     */
    function sendMessage() {
        const message = chatInput.value.trim();
        
        if (!message) {
            return;
        }
        
        // Add user message to the chat
        addUserMessage(message);
        
        // Clear input field
        chatInput.value = '';
        
        // Show typing indicator
        showTypingIndicator();
        
        // Send message to API
        sendMessageToAPI(message);
    }
    
    /**
     * Add a user message to the chat
     * @param {string} message - Message text
     */
    function addUserMessage(message) {
        const messageElement = document.createElement('div');
        messageElement.className = 'ai-chat-message ai-chat-user-message';
        
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        messageElement.innerHTML = `
            <div class="ai-chat-message-content">${escapeHtml(message)}</div>
            <div class="ai-chat-message-time">${time}</div>
        `;
        
        chatMessages.insertBefore(messageElement, chatTyping);
        scrollToBottom();
    }
    
    /**
     * Add a bot message to the chat
     * @param {string} message - Message text
     */
    function addBotMessage(message) {
        hideTypingIndicator();
        
        const messageElement = document.createElement('div');
        messageElement.className = 'ai-chat-message ai-chat-bot-message';
        
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        messageElement.innerHTML = `
            <div class="ai-chat-message-content">${escapeHtml(message)}</div>
            <div class="ai-chat-message-time">${time}</div>
        `;
        
        chatMessages.insertBefore(messageElement, chatTyping);
        scrollToBottom();
    }
    
    /**
     * Show the typing indicator
     */
    function showTypingIndicator() {
        if (!isTyping) {
            chatTyping.style.display = 'flex';
            isTyping = true;
            scrollToBottom();
        }
    }
    
    /**
     * Hide the typing indicator
     */
    function hideTypingIndicator() {
        if (isTyping) {
            chatTyping.style.display = 'none';
            isTyping = false;
        }
    }
    
    /**
     * Scroll the chat messages to the bottom
     */
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    /**
     * Send a message to the API
     * @param {string} message - Message text
     */
    function sendMessageToAPI(message) {
        // Disable input while waiting for response
        chatInput.disabled = true;
        chatSend.disabled = true;
        
        fetch(`${apiBaseUrl}/chat.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                session_id: sessionId,
                api_key: apiKey
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Add bot response after a small delay to simulate typing
                setTimeout(() => {
                    addBotMessage(data.response);
                    
                    // Re-enable input
                    chatInput.disabled = false;
                    chatSend.disabled = false;
                    chatInput.focus();
                }, 1000);
            } else {
                throw new Error(data.message || 'Error getting response');
            }
        })
        .catch(error => {
            console.error('AI Chat Widget Error:', error);
            
            // Add error message
            setTimeout(() => {
                addBotMessage('Sorry, I\'m having trouble connecting right now. Please try again later.');
                
                // Re-enable input
                chatInput.disabled = false;
                chatSend.disabled = false;
                chatInput.focus();
            }, 1000);
        });
    }
    
    /**
     * Escape HTML special characters
     * @param {string} html - String to escape
     * @return {string} Escaped string
     */
    function escapeHtml(html) {
        const div = document.createElement('div');
        div.textContent = html;
        return div.innerHTML;
    }
    
    // Public API
    const aiChat = function(method, ...args) {
        switch (method) {
            case 'init':
                init(args[0]);
                break;
            case 'open':
                if (widgetContainer) {
                    openChatWindow();
                }
                break;
            case 'close':
                if (widgetContainer) {
                    closeChatWindow();
                }
                break;
            default:
                console.error(`AI Chat Widget: Unknown method "${method}"`);
        }
    };
    
    // Expose the API
    window.aiChat = aiChat;
    
})(window);
