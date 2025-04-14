// File: public/js/chat.js

// WebSocket connection
let socket;
let currentUser;
let activeChat = null;
let activeChatType = null; // 'private' or 'group'
let chats = {};
let groups = {};
let friends = {};
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 5;
const RECONNECT_DELAY = 3000; // 3 seconds

// Initialize the chat application
function initChat(user, token) {
    currentUser = user;
    
    // Initialize WebSocket connection
    connectWebSocket(token);
    
    // Set up UI event listeners
    setupEventListeners();
    
    // Load initial data
    loadChats();
    loadGroups();
    loadFriends();
}

// Connect to WebSocket server
function connectWebSocket(token) {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const wsUrl = `${protocol}//${window.location.host}:${WEBSOCKET_PORT}/chat`;
    
    socket = new WebSocket(wsUrl);
    
    socket.onopen = function() {
        console.log('WebSocket connection established');
        reconnectAttempts = 0;
        
        // Send authentication token
        sendMessage({
            type: 'auth',
            data: { token }
        });
    };
    
    socket.onmessage = function(event) {
        const message = JSON.parse(event.data);
        handleWebSocketMessage(message);
    };
    
    socket.onclose = function() {
        console.log('WebSocket connection closed');
        
        // Attempt to reconnect
        if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
            reconnectAttempts++;
            setTimeout(() => {
                console.log(`Attempting to reconnect (${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS})...`);
                connectWebSocket(token);
            }, RECONNECT_DELAY);
        } else {
            showError('Connection lost. Please refresh the page to reconnect.');
        }
    };
    
    socket.onerror = function(error) {
        console.error('WebSocket error:', error);
    };
}

// Send message through WebSocket
function sendMessage(message) {
    if (socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify(message));
    } else {
        console.error('WebSocket is not connected');
        showError('Connection error. Please refresh the page.');
    }
}

// Handle incoming WebSocket messages
function handleWebSocketMessage(message) {
    switch (message.type) {
        case 'auth':
            handleAuthMessage(message.data);
            break;
        case 'chat_message':
            handleChatMessage(message.data);
            break;
        case 'user_connected':
        case 'user_disconnected':
            handleUserStatusChange(message.data);
            break;
        case 'chat_history':
            handleChatHistory(message.data);
            break;
        case 'error':
            showError(message.data.message);
            break;
        default:
            console.log('Unknown message type:', message.type);
    }
}

// Handle authentication message
function handleAuthMessage(data) {
    if (data.success) {
        console.log('Authentication successful');
    } else {
        console.error('Authentication failed:', data.message);
        showError('Authentication failed. Please refresh the page and try again.');
    }
}

// Handle chat message
function handleChatMessage(data) {
    // Add message to appropriate chat
    const chatId = data.room_id === 'general' ? 'general' : data.user_id === currentUser.id ? data.recipient_id : data.user_id;
    
    if (!chats[chatId]) {
        chats[chatId] = {
            id: chatId,
            name: data.username,
            messages: []
        };
        updateChatList();
    }
    
    chats[chatId].messages.push({
        id: data.id,
        senderId: data.user_id,
        senderName: data.username,
        message: data.message,
        timestamp: data.timestamp
    });
    
    // If this chat is active, update the UI
    if (activeChat === chatId) {
        appendMessageToUI(data);
    } else {
        // Increment unread count
        incrementUnreadCount(chatId);
    }
}

// Handle user status change
function handleUserStatusChange(data) {
    const user = data.user;
    
    // Update friend's status if in the list
    if (friends[user.id]) {
        friends[user.id].status = data.type === 'user_connected' ? 'online' : 'offline';
        updateFriendsList();
    }
    
    // Update chat list to reflect the status change
    updateChatList();
    
    // If the chat with this user is active, update status in header
    if (activeChat === user.id) {
        updateChatHeader();
    }
    
    // Show notification
    if (data.type === 'user_connected') {
        showNotification(`${user.username} is now online`);
    }
}

// Handle chat history
function handleChatHistory(data) {
    const chatId = data.chat_id;
    
    if (!chats[chatId]) {
        chats[chatId] = {
            id: chatId,
            name: data.chat_name,
            messages: []
        };
    }
    
    // Replace existing messages with history
    chats[chatId].messages = data.messages.map(msg => ({
        id: msg.id,
        senderId: msg.user_id,
        senderName: msg.username,
        message: msg.message,
        timestamp: msg.timestamp
    }));
    
    // If this chat is active, update the UI
    if (activeChat === chatId) {
        displayChatMessages(chatId);
    }
}

// Set up event listeners for UI elements
function setupEventListeners() {
    // Tab navigation
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tab;
            
            // Toggle active tab button
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            button.classList.add('active');
            
            // Toggle tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`${tab}-tab`).classList.add('active');
        });
    });
    
    // Send message button
    document.getElementById('send-message-btn').addEventListener('click', sendChatMessage);
    
    // Message input enter key
    document.getElementById('message-input').addEventListener('keypress', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChatMessage();
        }
    });
    
    // Create group button
    document.querySelector('.create-group-btn').addEventListener('click', () => {
        document.getElementById('create-group-modal').style.display = 'flex';
        populateFriendSelector();
    });
    
    // Add friend button
    document.querySelector('.add-friend-btn').addEventListener('click', () => {
        document.getElementById('add-friend-modal').style.display = 'flex';
    });
    
    // Close modals
    document.querySelectorAll('.close-modal, .cancel-btn').forEach(element => {
        element.addEventListener('click', () => {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        });
    });
    
    // Create group form submission
    document.getElementById('create-group-form').addEventListener('submit', e => {
        e.preventDefault();
        createGroup();
    });
    
    // Add friend form submission
    document.getElementById('add-friend-form').addEventListener('submit', e => {
        e.preventDefault();
        sendFriendRequest();
    });
    
    // Screen share button
    document.getElementById('screen-share-btn').addEventListener('click', () => {
        startScreenSharing();
    });
}

// Send a chat message
function sendChatMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (message && activeChat) {
        if (activeChatType === 'private') {
            sendMessage({
                type: 'private_message',
                data: {
                    recipient_id: activeChat,
                    message
                }
            });
        } else if (activeChatType === 'group') {
            sendMessage({
                type: 'chat_message',
                data: {
                    room_id: activeChat,
                    message
                }
            });
        }
        
        // Clear input
        input.value = '';
    }
}

// Load user's chats
function loadChats() {
    // Show loading state
    document.getElementById('private-chats').innerHTML = '<li class="chat-loading">Loading chats...</li>';
    
    // Fetch chats from the server
    fetch('/api/chats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chats = {};
                
                // Process chats
                data.chats.forEach(chat => {
                    chats[chat.id] = {
                        id: chat.id,
                        name: chat.name,
                        lastMessage: chat.last_message,
                        lastMessageTime: chat.last_message_time,
                        unreadCount: chat.unread_count,
                        status: chat.status
                    };
                });
                
                updateChatList();
            } else {
                console.error('Failed to load chats:', data.message);
                document.getElementById('private-chats').innerHTML = '<li class="error">Failed to load chats</li>';
            }
        })
        .catch(error => {
            console.error('Error loading chats:', error);
            document.getElementById('private-chats').innerHTML = '<li class="error">Failed to load chats</li>';
        });
}

// Load user's groups
function loadGroups() {
    // Show loading state
    document.getElementById('group-chats').innerHTML = '<li class="group-loading">Loading groups...</li>';
    
    // Fetch groups from the server
    fetch('/api/groups')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                groups = {};
                
                // Process groups
                data.groups.forEach(group => {
                    groups[group.id] = {
                        id: group.id,
                        name: group.name,
                        description: group.description,
                        memberCount: group.member_count,
                        lastMessage: group.last_message,
                        lastMessageTime: group.last_message_time,
                        unreadCount: group.unread_count
                    };
                });
                
                updateGroupList();
            } else {
                console.error('Failed to load groups:', data.message);
                document.getElementById('group-chats').innerHTML = '<li class="error">Failed to load groups</li>';
            }
        })
        .catch(error => {
            console.error('Error loading groups:', error);
            document.getElementById('group-chats').innerHTML = '<li class="error">Failed to load groups</li>';
        });
}

// Load user's friends
function loadFriends() {
    // Show loading state
    document.getElementById('friends-list').innerHTML = '<li class="friend-loading">Loading friends...</li>';
    
    // Fetch friends from the server
    fetch('/api/friends')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                friends = {};
                
                // Process friends
                data.friends.forEach(friend => {
                    friends[friend.id] = {
                        id: friend.id,
                        username: friend.username,
                        avatar: friend.avatar,
                        status: friend.status
                    };
                });
                
                updateFriendsList();
            } else {
                console.error('Failed to load friends:', data.message);
                document.getElementById('friends-list').innerHTML = '<li class="error">Failed to load friends</li>';
            }
        })
        .catch(error => {
            console.error('Error loading friends:', error);
            document.getElementById('friends-list').innerHTML = '<li class="error">Failed to load friends</li>';
        });
}

// Update the chat list UI
function updateChatList() {
    const chatList = document.getElementById('private-chats');
    const template = document.getElementById('chat-item-template');
    
    // Clear current list except search bar
    chatList.innerHTML = '';
    
    // Sort chats by last message time
    const sortedChats = Object.values(chats).sort((a, b) => {
        return (b.lastMessageTime || 0) - (a.lastMessageTime || 0);
    });
    
    if (sortedChats.length === 0) {
        chatList.innerHTML = '<li class="no-chats">No chats yet</li>';
        return;
    }
    
    // Add each chat to the list
    sortedChats.forEach(chat => {
        const clone = template.content.cloneNode(true);
        const item = clone.querySelector('.chat-item');
        
        item.dataset.id = chat.id;
        item.querySelector('.chat-name').textContent = chat.name;
        item.querySelector('.chat-last-message').textContent = chat.lastMessage || 'No messages yet';
        item.querySelector('.chat-time').textContent = formatTime(chat.lastMessageTime);
        
        const unreadCount = item.querySelector('.unread-count');
        if (chat.unreadCount && chat.unreadCount > 0) {
            unreadCount.textContent = chat.unreadCount;
            unreadCount.style.display = 'flex';
        } else {
            unreadCount.style.display = 'none';
        }
        
        const statusIndicator = item.querySelector('.status-indicator');
        statusIndicator.className = 'status-indicator ' + (chat.status || 'offline');
        
        // Add click event to select chat
        item.addEventListener('click', () => {
            selectChat(chat.id, 'private');
        });
        
        chatList.appendChild(clone);
    });
}

// Update the group list UI
function updateGroupList() {
    const groupList = document.getElementById('group-chats');
    const template = document.getElementById('chat-item-template');
    
    // Clear current list except search bar
    groupList.innerHTML = '';
    
    // Sort groups by last message time
    const sortedGroups = Object.values(groups).sort((a, b) => {
        return (b.lastMessageTime || 0) - (a.lastMessageTime || 0);
    });
    
    if (sortedGroups.length === 0) {
        groupList.innerHTML = '<li class="no-groups">No groups yet</li>';
        return;
    }
    
    // Add each group to the list
    sortedGroups.forEach(group => {
        const clone = template.content.cloneNode(true);
        const item = clone.querySelector('.chat-item');
        
        item.dataset.id = group.id;
        item.querySelector('.chat-name').textContent = group.name;
        item.querySelector('.chat-last-message').textContent = group.lastMessage || 'No messages yet';
        item.querySelector('.chat-time').textContent = formatTime(group.lastMessageTime);
        
        const unreadCount = item.querySelector('.unread-count');
        if (group.unreadCount && group.unreadCount > 0) {
            unreadCount.textContent = group.unreadCount;
            unreadCount.style.display = 'flex';
        } else {
            unreadCount.style.display = 'none';
        }
        
        // Group has no online status
        item.querySelector('.status-indicator').style.display = 'none';
        
        // Add click event to select group
        item.addEventListener('click', () => {
            selectChat(group.id, 'group');
        });
        
        groupList.appendChild(clone);
    });
}

// Update the friends list UI
function updateFriendsList() {
    const friendsList = document.getElementById('friends-list');
    
    // Clear current list except search bar
    friendsList.innerHTML = '';
    
    // Sort friends by online status and then by username
    const sortedFriends = Object.values(friends).sort((a, b) => {
        if (a.status === 'online' && b.status !== 'online') return -1;
        if (a.status !== 'online' && b.status === 'online') return 1;
        return a.username.localeCompare(b.username);
    });
    
    if (sortedFriends.length === 0) {
        friendsList.innerHTML = '<li class="no-friends">No friends yet</li>';
        return;
    }
    
    // Add each friend to the list
    sortedFriends.forEach(friend => {
        const item = document.createElement('li');
        item.className = 'friend-item';
        item.dataset.id = friend.id;
        
        item.innerHTML = `
            <div class="friend-avatar">
                <img src="${friend.avatar || '/public/assets/images/default-avatar.png'}" alt="${friend.username}">
                <span class="status-indicator ${friend.status || 'offline'}"></span>
            </div>
            <div class="friend-info">
                <h4 class="friend-name">${friend.username}</h4>
                <p class="friend-status">${friend.status === 'online' ? 'Online' : 'Offline'}</p>
            </div>
            <div class="friend-actions">
                <button class="start-chat-btn" title="Start Chat"><i class="icon-chat"></i></button>
                <button class="more-options-btn" title="More Options"><i class="icon-menu"></i></button>
            </div>
        `;
        
        // Add click event to start chat
        item.querySelector('.start-chat-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            selectChat(friend.id, 'private');
            
            // Switch to chats tab
            document.querySelector('.tab-button[data-tab="chats"]').click();
        });
        
        friendsList.appendChild(item);
    });
}

// Select a chat or group
function selectChat(id, type) {
    activeChat = id;
    activeChatType = type;
    
    // Update UI
    document.querySelectorAll('.chat-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.id === id) {
            item.classList.add('active');
        }
    });
    
    // Enable chat input
    document.getElementById('message-input').disabled = false;
    document.getElementById('send-message-btn').disabled = false;
    document.getElementById('screen-share-btn').disabled = false;
    
    // Enable other actions
    document.getElementById('voice-call-btn').disabled = type !== 'private';
    document.getElementById('video-call-btn').disabled = type !== 'private';
    document.getElementById('chat-menu-btn').disabled = false;
    
    // Reset unread count
    if (type === 'private' && chats[id]) {
        chats[id].unreadCount = 0;
        updateChatList();
    } else if (type === 'group' && groups[id]) {
        groups[id].unreadCount = 0;
        updateGroupList();
    }
    
    // Update chat header
    updateChatHeader();
    
    // Load chat history
    loadChatHistory(id, type);
}

// Update the chat header based on the selected chat