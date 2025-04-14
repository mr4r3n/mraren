<?php
/**
 * WebSocket Server Handler
 * 
 * This file initializes the WebSocket server and handles different events
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Create WebSocket server
$server = new WebSocketServer(WEBSOCKET_HOST, WEBSOCKET_PORT, MongoDB_Connection::getInstance());

// Handle connection event
$server->on('connection', function($client, $data, $server) {
    // Send welcome message
    $server->send($client, [
        'type' => 'system',
        'data' => [
            'message' => 'Welcome to the chat server!'
        ]
    ]);
});

// Handle authentication event
$server->on('auth', function($client, $data, $server) {
    // User authenticated, join global chat
    $userId = $server->getUserId($client);
    
    if ($userId) {
        // Get user data
        $db = MongoDB_Connection::getInstance();
        $user = $db->findById('users', $userId);
        
        if ($user) {
            // Join general chat room
            $server->broadcast([
                'type' => 'user_connected',
                'data' => [
                    'user' => [
                        'id' => (string)$user->_id,
                        'username' => $user->username
                    ],
                    'message' => $user->username . ' has joined the chat'
                ]
            ], [$client]);
        }
    }
});

// Handle chat message event
$server->on('chat_message', function($client, $data, $server) {
    $userId = $server->getUserId($client);
    
    if (!$userId) {
        // User not authenticated
        $server->send($client, [
            'type' => 'error',
            'data' => [
                'message' => 'You must be authenticated to send messages'
            ]
        ]);
        
        return;
    }
    
    // Validate message
    if (!isset($data['message']) || trim($data['message']) === '') {
        return;
    }
    
    // Get user data
    $db = MongoDB_Connection::getInstance();
    $user = $db->findById('users', $userId);
    
    if (!$user) {
        return;
    }
    
    // Get room ID
    $roomId = isset($data['room_id']) ? $data['room_id'] : 'general';
    
    // Create message document
    $message = [
        'user_id' => new MongoDB\BSON\ObjectId($userId),
        'room_id' => $roomId,
        'username' => $user->username,
        'message' => $data['message'],
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];
    
    // Save message to database
    $db->insert('messages', $message);
    
    // Broadcast message to all clients
    $server->broadcast([
        'type' => 'chat_message',
        'data' => [
            'id' => (string)$message['_id'],
            'user_id' => $userId,
            'room_id' => $roomId,
            'username' => $user->username,
            'message' => $data['message'],
            'timestamp' => time()
        ]
    ]);
});

// Handle private message event
$server->on('private_message', function($client, $data, $server) {
    $userId = $server->getUserId($client);
    
    if (!$userId) {
        // User not authenticated
        $server->send($client, [
            'type' => 'error',
            'data' => [
                'message' => 'You must be authenticated to send private messages'
            ]
        ]);
        
        return;
    }
    
    // Validate message
    if (!isset($data['message']) || trim($data['message']) === '' || !isset($data['recipient_id'])) {
        return;
    }
    
    // Get user data
    $db = MongoDB_Connection::getInstance();
    $user = $db->findById('users', $userId);
    
    if (!$user) {
        return;
    }
    
    // Create message document
    $message = [
        'sender_id' => new MongoDB\BSON\ObjectId($userId),
        'recipient_id' => new MongoDB\BSON\ObjectId($data['recipient_id']),
        'sender_username' => $user->username,
        'message' => $data['message'],
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'read' => false
    ];
    
    // Save message to database
    $db->insert('private_messages', $message);
    
    // Find recipient client
    $recipientClient = null;
    
    foreach ($server->clients as $c) {
        $recipientUserId = $server->getUserId($c);
        
        if ($recipientUserId && $recipientUserId === $data['recipient_id']) {
            $recipientClient = $c;
            break;
        }
    }
    
    // Send message to recipient if online
    if ($recipientClient) {
        $server->send($recipientClient, [
            'type' => 