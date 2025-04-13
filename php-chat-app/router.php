<?php
/**
 * Router Class
 * 
 * Handles application routing without using a framework or Composer
 */

class Router {
    private $routes = [];
    private $notFoundCallback;
    
    /**
     * Add a route to the router
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path Route path
     * @param callable $callback Callback function
     * @param array $middleware Optional middleware functions
     */
    public function addRoute($method, $path, $callback, $middleware = []) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback,
            'middleware' => $middleware
        ];
    }
    
    /**
     * Add a GET route
     * 
     * @param string $path Route path
     * @param callable $callback Callback function
     * @param array $middleware Optional middleware functions
     */
    public function get($path, $callback, $middleware = []) {
        $this->addRoute('GET', $path, $callback, $middleware);
    }
    
    /**
     * Add a POST route
     * 
     * @param string $path Route path
     * @param callable $callback Callback function
     * @param array $middleware Optional middleware functions
     */
    public function post($path, $callback, $middleware = []) {
        $this->addRoute('POST', $path, $callback, $middleware);
    }
    
    /**
     * Add a PUT route
     * 
     * @param string $path Route path
     * @param callable $callback Callback function
     * @param array $middleware Optional middleware functions
     */
    public function put($path, $callback, $middleware = []) {
        $this->addRoute('PUT', $path, $callback, $middleware);
    }
    
    /**
     * Add a DELETE route
     * 
     * @param string $path Route path
     * @param callable $callback Callback function
     * @param array $middleware Optional middleware functions
     */
    public function delete($path, $callback, $middleware = []) {
        $this->addRoute('DELETE', $path, $callback, $middleware);
    }
    
    /**
     * Set a 404 Not Found callback
     * 
     * @param callable $callback Callback function
     */
    public function setNotFoundHandler($callback) {
        $this->notFoundCallback = $callback;
    }
    
    /**
     * Parse the URL parameters from a route pattern and URI
     * 
     * @param string $pattern Route pattern
     * @param string $uri Requested URI
     * @return array|false Parameters if matched, false otherwise
     */
    private function parseParams($pattern, $uri) {
        // Convert route pattern to regex
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        // Match URI against pattern
        if (preg_match($pattern, $uri, $matches)) {
            $params = [];
            
            // Extract named parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            
            return $params;
        }
        
        return false;
    }
    
    /**
     * Match a route to the requested URI and method
     * 
     * @param string $method HTTP method
     * @param string $uri Requested URI
     * @return array|false Matched route or false
     */
    private function matchRoute($method, $uri) {
        foreach ($this->routes as $route) {
            // Check HTTP method
            if ($route['method'] !== $method) {
                continue;
            }
            
            // Check for exact match
            if ($route['path'] === $uri) {
                return [
                    'route' => $route,
                    'params' => []
                ];
            }
            
            // Check for parameterized match
            $params = $this->parseParams($route['path'], $uri);
            if ($params !== false) {
                return [
                    'route' => $route,
                    'params' => $params
                ];
            }
        }
        
        return false;
    }
    
    /**
     * Execute middleware functions
     * 
     * @param array $middleware Middleware functions
     * @param array $params Route parameters
     * @return bool True if all middleware passed, false otherwise
     */
    private function executeMiddleware($middleware, $params) {
        foreach ($middleware as $func) {
            $result = call_user_func($func, $params);
            if ($result === false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Handle the current request
     */
    public function handle() {
        // Get the request method
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Handle PUT and DELETE methods via POST with _method parameter
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        // Get the request URI
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove the base path from the URI if needed
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Normalize URI
        $uri = '/' . trim($uri, '/');
        
        // Match route
        $match = $this->matchRoute($method, $uri);
        
        if ($match) {
            $route = $match['route'];
            $params = $match['params'];
            
            // Execute middleware
            if (!empty($route['middleware']) && !$this->executeMiddleware($route['middleware'], $params)) {
                // Middleware rejected the request
                header('HTTP/1.1 403 Forbidden');
                echo 'Forbidden';
                return;
            }
            
            // Execute the route callback
            call_user_func($route['callback'], $params);
        } else {
            // No route matched
            if (isset($this->notFoundCallback)) {
                call_user_func($this->notFoundCallback);
            } else {
                header('HTTP/1.1 404 Not Found');
                echo 'Page not found';
            }
        }
    }
}
