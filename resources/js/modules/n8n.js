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
            i18n: {
                en: {
                    title: 'Welcome!',
                    subtitle: "Ask me anything.",
                    getStarted: 'Start Chatting',
                    inputPlaceholder: 'Enter your message here...'
                }
            },
            theme: {
                colors: {
                    primary: '#4285f4'
                }
            }
        });
    } else {
        console.warn('`createChat` function not found or webhook URL is missing. n8n chat widget will not be loaded.');
    }
}
