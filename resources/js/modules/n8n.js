// --- N8N Chat Widget Initialization ---
export function initializeN8nChat() {
    if (document.getElementById('adminSidebar')) {
        return; // Exit early if on admin page where chat is not needed
    }

    // Ensure required global variables are present
    if (typeof window.userEmail === 'undefined' || typeof window.chatWebhookUrl === 'undefined') {
        console.debug('n8n chat cannot initialize: required user or webhook data is missing.');
        return;
    }

    const currentUserEmail = window.userEmail;
    const currentUserId = window.userId;
    const currentUsername = window.username;
    const n8nWebhookUrlToUse = window.chatWebhookUrl;
    const isPremium = window.userIsPremium || false;

    // Customize initial messages based on premium status
    const initialMessages = isPremium 
        ? [
            'Hello, valued premium member!',
            'My name is Tubby, your premium assistant. How can I help you today?',
            'You have access to our premium support features.'
          ]
        : [
            'Hello!',
            'My name is Tubby. How can I assist you today?',
            'Upgrade to premium for personalized support and advanced features.'
          ];

    if (window.createChat && n8nWebhookUrlToUse) {
        window.createChat({
            webhookUrl: n8nWebhookUrlToUse,
            webhookConfig: {
                method: 'POST',
                headers: {}
            },
            target: '#n8n-chat-container',
            mode: 'window',
            chatInputKey: 'chatInput',
            chatSessionKey: 'sessionId',
            metadata: {
                ...(isPremium && { userId: currentUserId }),
                userEmail: currentUserEmail,
                userName: currentUsername,
                isPremium: isPremium
            },
            showWelcomeScreen: false,
            defaultLanguage: 'en',
            initialMessages: initialMessages,
            branding: {
                logo: '/hero-clipart-3.png',
                name: 'Tubby'
            },
            style: {
                primaryColor: '#24243B',
                secondaryColor: '#24243B',
                position: 'bottom-right',
                backgroundColor: '#1F2235',
                fontColor: '#E5E7EB'
            },
            i18n: {
                en: {
                    title: 'Welcome!',
                    subtitle: "Ask me anything.",
                    getStarted: 'Start Chatting',
                    inputPlaceholder: 'Enter your message here...'
                }
            }
        });

        // Force override chat button after widget loads
        setTimeout(() => {
            // Target the chat toggle button
            const chatToggle = document.querySelector('.chat-window-toggle');
            if (chatToggle) {
                // Change background color
                chatToggle.style.backgroundColor = '#24243B';
                chatToggle.style.setProperty('background-color', '#24243B', 'important');
                
                // Hide the SVG icon but don't remove it (n8n needs it for state management)
                const svg = chatToggle.querySelector('svg');
                if (svg && !chatToggle.querySelector('.tubby-icon')) {
                    // Initially hide SVG since chat starts closed
                    svg.style.display = 'none';
                    
                    // Add tubby image as background or overlay
                    const img = document.createElement('img');
                    img.className = 'tubby-icon';
                    img.src = '/hero-clipart-3.png';
                    img.style.width = '40px';
                    img.style.height = '40px';
                    img.style.borderRadius = '50%';
                    img.style.objectFit = 'cover';
                    img.style.position = 'absolute';
                    img.style.top = '50%';
                    img.style.left = '50%';
                    img.style.transform = 'translate(-50%, -50%)';
                    img.style.pointerEvents = 'none'; // Let clicks pass through to button
                    img.style.display = 'block'; // Initially visible since chat is closed
                    
                    chatToggle.style.position = 'relative';
                    chatToggle.appendChild(img);
                    
                    // Add click handler to close dropdowns when chat is opened
                    chatToggle.addEventListener('click', function() {
                        // Close all dropdowns when chatbot is clicked
                        if (window.closeAllDropdowns) {
                            window.closeAllDropdowns();
                        }
                    });
                    
                    // Function to update icon visibility based on chat state
                    const updateIcons = () => {
                        const chatWindow = document.querySelector('.chat-window');
                        if (chatWindow) {
                            const computedStyle = window.getComputedStyle(chatWindow);
                            const isClosed = computedStyle.display === 'none';
                            
                            // When chat is CLOSED: show custom icon, hide SVG
                            // When chat is OPEN: hide custom icon, show SVG caret
                            img.style.display = isClosed ? 'block' : 'none';
                            svg.style.display = isClosed ? 'none' : 'block';
                            
                            console.log('Chat display:', computedStyle.display, '| Closed:', isClosed, '| Custom:', img.style.display, '| SVG:', svg.style.display);
                        }
                    };
                    
                    // Watch for clicks on the toggle button to swap icons
                    chatToggle.addEventListener('click', () => {
                        setTimeout(updateIcons, 100);
                    });
                    
                    // Set initial state
                    setTimeout(updateIcons, 200);
                }
                
                console.log('Chat button customized: color #24243B, icon hero-clipart-3.png');
            }

            // Also change the send button color
            const sendButton = document.querySelector('.chat-input-send-button');
            if (sendButton) {
                sendButton.style.setProperty('background-color', '#24243B', 'important');
            }
        }, 1500);
    } else {
        // Downgrade to debug to avoid alarming users when chat is intentionally disabled
        console.debug('n8n chat disabled: createChat not present or webhook missing.');
    }
}
