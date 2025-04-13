<?php
/**
 * MongoDB Database Configuration
 * 
 * This file handles the connection to MongoDB without using Composer dependencies
 */

class MongoDB_Connection {
    private static $instance = null;
    private $connection = null;
    private $database = null;
    
    // Database configuration
    private $host = '127.0.0.1';
    private $port = 27017;
    private $dbname = 'php_chat_app';
    private $username = '';
    private $password = '';
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Create MongoDB connection
        try {
            if (!extension_loaded('mongodb')) {
                throw new Exception('MongoDB extension is not loaded');
            }
            
            // Build connection string
            $connectionString = 'mongodb://';
            
            // Add authentication if credentials are provided
            if (!empty($this->username) && !empty($this->password)) {
                $connectionString .= urlencode($this->username) . ':' . urlencode($this->password) . '@';
            }
            
            $connectionString .= $this->host . ':' . $this->port;
            
            // Create the MongoDB client
            $this->connection = new MongoDB\Driver\Manager($connectionString);
            
        } catch (Exception $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get MongoDB connection instance (Singleton pattern)
     * 
     * @return MongoDB_Connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Get MongoDB connection
     * 
     * @return MongoDB\Driver\Manager
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a MongoDB command
     * 
     * @param string $collection Collection name
     * @param array $filter Query filter
     * @param array $options Query options
     * @return MongoDB\Driver\Cursor
     */
    public function find($collection, $filter = [], $options = []) {
        $query = new MongoDB\Driver\Query($filter, $options);
        return $this->connection->executeQuery($this->dbname . '.' . $collection, $query);
    }
    
    /**
     * Insert a document into a collection
     * 
     * @param string $collection Collection name
     * @param array $document Document to insert
     * @return MongoDB\Driver\WriteResult
     */
    public function insert($collection, $document) {
        $bulk = new MongoDB\Driver\BulkWrite();
        
        // If the document doesn't have an _id, MongoDB will generate one
        if (!isset($document['_id'])) {
            $document['_id'] = new MongoDB\BSON\ObjectId();
        }
        
        $bulk->insert($document);
        
        return $this->connection->executeBulkWrite($this->dbname . '.' . $collection, $bulk);
    }
    
    /**
     * Update documents in a collection
     * 
     * @param string $collection Collection name
     * @param array $filter Query filter
     * @param array $update Update operations
     * @param array $options Update options
     * @return MongoDB\Driver\WriteResult
     */
    public function update($collection, $filter, $update, $options = ['multi' => false, 'upsert' => false]) {
        $bulk = new MongoDB\Driver\BulkWrite();
        
        $bulk->update($filter, $update, $options);
        
        return $this->connection->executeBulkWrite($this->dbname . '.' . $collection, $bulk);
    }
    
    /**
     * Delete documents from a collection
     * 
     * @param string $collection Collection name
     * @param array $filter Query filter
     * @param array $options Delete options
     * @return MongoDB\Driver\WriteResult
     */
    public function delete($collection, $filter, $options = ['limit' => 1]) {
        $bulk = new MongoDB\Driver\BulkWrite();
        
        $bulk->delete($filter, $options);
        
        return $this->connection->executeBulkWrite($this->dbname . '.' . $collection, $bulk);
    }
    
    /**
     * Execute an aggregation pipeline
     * 
     * @param string $collection Collection name
     * @param array $pipeline Aggregation pipeline
     * @param array $options Aggregation options
     * @return MongoDB\Driver\Cursor
     */
    public function aggregate($collection, $pipeline, $options = []) {
        $command = new MongoDB\Driver\Command([
            'aggregate' => $collection,
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);
        
        return $this->connection->executeCommand($this->dbname, $command);
    }
    
    /**
     * Get a document by ID
     * 
     * @param string $collection Collection name
     * @param string $id Document ID
     * @return object|null
     */
    public function findById($collection, $id) {
        $filter = ['_id' => new MongoDB\BSON\ObjectId($id)];
        $result = $this->find($collection, $filter);
        
        foreach ($result as $document) {
            return $document; // Return the first (and only) document
        }
        
        return null;
    }
}
