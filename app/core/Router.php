<?php
/**
 * Router - Simple Request Router
 */

class Router {
    private array $routes = [];
    
    /**
     * Register a route
     */
    public function register(string $action, callable $handler): void {
        $this->routes[$action] = $handler;
    }
    
    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(string $action): bool {
        if (!isset($this->routes[$action])) {
            return false;
        }
        
        call_user_func($this->routes[$action]);
        return true;
    }
}
