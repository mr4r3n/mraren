<?php
/**
 * WebSocket Server Class
 * 
 * Implements WebSocket protocol for real-time communication
 */

class WebSocketServer {
    private $socket;
    private $clients = [];
    private $handshakes = [];
    private $eventListeners = [];
    private $db;
    private $userSessions = [];
    
    /**
     * Constructor
     * 
     * @param string $host WebSocket host
     * @param int $port WebSocket port
     * @param MongoDB_Connection $db Database connection
     */
    public function __construct($host = '127.0.0.1', $port = 8080, $db = null) {
        // Create TCP/IP socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        // Set socket options
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        
        // Bind socket to specified host and port
        socket_bind($this->socket, $host, $port);
        
        // Start listening for connections
        socket_listen($this->socket);
        
        // Set socket to non-blocking mode
        socket_set_nonblock($this->socket);
        
        // Set database connection
        $this->db = $db;
        
        // Log server start
        $this->log("WebSocket server started on $host:$port");
    }
    
    /**
     * Run the WebSocket server
     */
    public function run() {
        while (true) {
            // Accept new connections
            $this->acceptNewConnections();
            
            // Handle client data
            $this->handleClientData();
            
            // Sleep to reduce CPU usage
            usleep(10000); // 10ms
        }
    }
    
    /**
     * Accept new connections
     */
    private function acceptNewConnections() {
        $newSocket = @socket_accept($this->socket);
        
        if ($newSocket !== false) {
            // Set socket to non-blocking mode
            socket_set_nonblock($newSocket);
            
            // Add new client to clients array
            $this->clients[] = $newSocket;
            
            // Log new connection
            $this->log("New client connected: " . $this->getClientId($newSocket));
        }
    }
    
    /**
     * Handle client data
     */
    private function handleClientData() {
        foreach ($this->clients as $clientIndex => $client) {
            $data = @socket_read($client, 1024, PHP_NORMAL_READ);
            
            if ($data === false) {
                // Error reading from client
                $error = socket_last_error($client);
                
                if ($error != SOCKET_EAGAIN && $error != SOCKET_EWOULDBLOCK) {
                    // Client disconnected or error occurred
                    $this->disconnectClient($clientIndex);
                }
                
                continue;
            }
            
            if ($data === '' || $data === null) {
                // Empty data, client probably disconnected
                $this->disconnectClient($clientIndex);
                continue;
            }
            
            $clientId = $this->getClientId($client);
            
            // Check if handshake is complete
            if (!isset($this->handshakes[$clientId])) {
                // Handle WebSocket handshake
                $this->handleHandshake($client, $data);
            } else {
                // Handle WebSocket frame
                $this->handleWebSocketFrame($client, $data);
            }
        }
    }
    
    /**
     * Handle WebSocket handshake
     * 
     * @param resource $client Client socket
     * @param string $data Client data
     */
    private function handleHandshake($client, $data) {
        $clientId = $this->getClientId($client);
        
        // Check if it's a valid WebSocket handshake
        if (preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $data, $matches)) {
            $secWebSocketKey = trim($matches[1]);
            
            // Calculate response key
            $secWebSocketAccept = base64_encode(pack('H*', sha1($secWebSocketKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            
            // Create handshake response
            $response = "HTTP/1.1 101 Switching Protocols\r\n";
            $response .= "Upgrade: websocket\r\n";
            $response .= "Connection: Upgrade\r\n";
            $response .= "Sec-WebSocket-Accept: $secWebSocketAccept\r\n";
            $response .= "\r\n";
            
            // Send response
            socket_write($client, $response, strlen($response));
            
            // Mark handshake as complete
            $this->handshakes[$clientId] = true;
            
            // Log successful handshake
            $this->log("Handshake complete for client: $clientId");
            
            // Check for authentication token in headers
            if (preg_match('/Authorization: Bearer (.*)\r\n/', $data, $matches)) {
                $token = trim($matches[1]);
                
                // Authenticate user
                $this->authenticateUser($client, $token);
            }
            
            // Trigger connection event
            $this->triggerEvent('connection', $client, null);
        } else {
            // Invalid handshake
            $this->log("Invalid handshake from client: $clientId");
            $this->disconnectClient(array_search($client, $this->clients));
        }
    }
    
    /**
     * Handle WebSocket frame
     * 
     * @param resource $client Client socket
     * @param string $data Frame data
     */
    private function handleWebSocketFrame($client, $data) {
        $clientId = $this->getClientId($client);
        
        // Unmask and decode WebSocket frame
        $frame = $this->decodeWebSocketFrame($data);
        
        if (!$frame) {
            return;
        }
        
        // Handle different frame types
        switch ($frame['opcode']) {
            case 0x1: // Text frame
                // Decode message as JSON
                $message = json_decode($frame['payload'], true);
                
                if ($message && isset($message['type'])) {
                    // Trigger event based on message type
                    $this->triggerEvent($message['type'], $client, $message['data'] ?? null);
                }
                break;
                
            case 0x8: // Close frame
                // Client requested to close connection
                $this->disconnectClient(array_search($client, $this->clients));
                break;
                
            case 0x9: // Ping frame
                // Respond with pong
                $this->sendPong($client);
                break;
                
            case 0xA: // Pong frame
                // Ignore pong frames
                break;
        }
    }
    
    /**
     * Decode WebSocket frame
     * 
     * @param string $data Frame data
     * @return array|false Decoded frame or false on error
     */
    private function decodeWebSocketFrame($data) {
        $dataLength = strlen($data);
        
        if ($dataLength < 2) {
            return false;
        }
        
        // Read first byte
        $firstByte = ord($data[0]);
        $fin = ($firstByte & 0x80) !== 0;
        $opcode = $firstByte & 0x0F;
        
        // Read second byte
        $secondByte = ord($data[1]);
        $masked = ($secondByte & 0x80) !== 0;
        $payloadLength = $secondByte & 0x7F;
        
        $offset = 2;
        
        // Extended payload length (16 bit)
        if ($payloadLength === 126) {
            if ($dataLength < 4) {
                return false;
            }
            
            $payloadLength = unpack('n', substr($data, 2, 2))[1];
            $offset += 2;
        }
        // Extended payload length (64 bit)
        else if ($payloadLength === 127) {
            if ($dataLength < 10) {
                return false;
            }
            
            $payloadLength = unpack('N', substr($data, 2, 4))[1] * 65536 + unpack('N', substr($data, 6, 4))[1];
            $offset += 8;
        }
        
        // Read masking key
        if ($masked) {
            if ($dataLength < $offset + 4) {
                return false;
            }
            
            $maskingKey = substr($data, $offset, 4);
            $offset += 4;
        }
        
        // Check if we have enough data
        if ($dataLength < $offset + $payloadLength) {
            return false;
        }
        
        // Read payload
        $payload = substr($data, $offset, $payloadLength);
        
        // Unmask payload if masked
        if ($masked) {
            $unmaskedPayload = '';
            
            for ($i = 0; $i < $payloadLength; $i++) {
                $unmaskedPayload .= $payload[$i] ^ $maskingKey[$i % 4];
            }
            
            $payload = $unmaskedPayload;
        }
        
        return [
            'fin' => $fin,
            'opcode' => $opcode,
            'masked' => $masked,
            'payload' => $payload
        ];
    }
    
    /**
     * Encode and send WebSocket frame
     * 
     * @param resource $client Client socket
     * @param string $payload Frame payload
     * @param int $opcode Frame opcode
     */
    private function sendWebSocketFrame($client, $payload, $opcode = 0x1) {
        $payloadLength = strlen($payload);
        
        // First byte: FIN bit (1) + opcode (4 bits)
        $frame = chr(0x80 | $opcode);
        
        // Second byte: MASK bit (0) + payload length (7 bits)
        if ($payloadLength <= 125) {
            $frame .= chr($payloadLength);
        } else if ($payloadLength <= 65535) {
            $frame .= chr(126) . pack('n', $payloadLength);
        } else {
            $frame .= chr(127) . pack('N', 0) . pack('N', $payloadLength);
        }
        
        // Append payload
        $frame .= $payload;
        
        // Send frame
        socket_write($client, $frame, strlen($frame));
    }
    
    /**
     * Send data to a client
     * 
     * @param resource $client Client socket
     * @param mixed $data Data to send
     */
    public function send($client, $data) {
        // Convert data to JSON
        $json = json_encode($data);
        
        // Send WebSocket frame
        $this->sendWebSocketFrame($client, $json);
    }
    
    /**
     * Send data to all clients
     * 
     * @param mixed $data Data to send
     * @param array $exclude Clients to exclude
     */
    public function broadcast($data, $exclude = []) {
        foreach ($this->clients as $client) {
            if (!in_array($client, $exclude)) {
                $this->send($client, $data);
            }
        }
    }
    
    /**
     * Send pong frame
     * 
     * @param resource $client Client socket
     */
    private function sendPong($client) {
        $this->sendWebSocketFrame($client, '', 0xA);
    }
    
    /**
     * Disconnect a client
     * 
     * @param int $clientIndex Client index in clients array
     */
    private function disconnectClient($clientIndex) {
        if (!isset($this->clients[$clientIndex])) {
            return;
        }
        
        $client = $this->clients[$clientIndex];
        $clientId = $this->getClientId($client);
        
        // Remove from handshakes
        if (isset($this->handshakes[$clientId])) {
            unset($this->handshakes[$clientId]);
        }
        
        // Remove from user sessions
        if (isset($this->userSessions[$clientId])) {
            unset($this->userSessions[$clientId]);
        }
        
        // Trigger disconnect event
        $this->triggerEvent('disconnect', $client, null);
        
        // Close socket
        socket_close($client);
        
        // Remove from clients array
        unset($this->clients[$clientIndex]);
        
        // Log disconnect
        $this->log("Client disconnected: $clientId");
    }
    
    /**
     * Get client ID (IP address and port)
     * 
     * @param resource $client Client socket
     * @return string Client ID
     */
    private function getClientId($client) {
        socket_getpeername($client, $ip, $port);
        return "$ip:$port";
    }
    
    /**
     * Get user ID for a client
     * 
     * @param resource $client Client socket
     * @return string|null User ID or null if not authenticated
     */
    public function getUserId($client) {
        $clientId = $this->getClientId($client);
        
        return isset($this->userSessions[$clientId]) ? $this->userSessions[$clientId] : null;
    }
    
    /**
     * Authenticate user
     * 
     * @param resource $client Client socket
     * @param string $token Authentication token
     */
    private function authenticateUser($client, $token) {
        // Verify JWT token
        $auth = Auth::getInstance();
        $payload = $auth->verifyJWT($token);
        
        if ($payload && isset($payload['id'])) {
            $clientId = $this->getClientId($client);
            
            // Store user ID in sessions
            $this->userSessions[$clientId] = $payload['id'];
            
            // Log authentication
            $this->log("Client authenticated: $clientId (User ID: {$payload['id']})");
            
            // Send authentication success message
            $this->send($client, [
                'type' => 'auth',
                'data' => [
                    'success' => true,
                    'message' => 'Authentication successful'
                ]
            ]);
            
            // Trigger authentication event
            $this->triggerEvent('auth', $client, $payload);
        } else {
            // Send authentication failure message
            $this->send($client, [
                'type' => 'auth',
                'data' => [
                    'success' => false,
                    'message' => 'Authentication failed'
                ]
            ]);
        }
    }
    
    /**
     * Add event listener
     * 
     * @param string $event Event name
     * @param callable $callback Event callback
     */
    public function on($event, $callback) {
        if (!isset($this->eventListeners[$event])) {
            $this->eventListeners[$event] = [];
        }
        
        $this->eventListeners[$event][] = $callback;
    }
    
    /**
     * Trigger event
     * 
     * @param string $event Event name
     * @param resource $client Client socket
     * @param mixed $data Event data
     */
    private function triggerEvent($event, $client, $data) {
        if (!isset($this->eventListeners[$event])) {
            return;
        }
        
        foreach ($this->eventListeners[$event] as $callback) {
            call_user_func($callback, $client, $data, $this);
        }
    }
    
    /**
     * Log message
     * 
     * @param string $message Log message
     */
    private function log($message) {
        $date = date('Y-m-d H:i:s');
        echo "[$date] $message\n";
    }
}
