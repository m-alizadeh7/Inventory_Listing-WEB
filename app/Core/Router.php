<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function get($path, $handler) {
        $this->add('GET', $path, $handler);
    }

    public function post($path, $handler) {
        $this->add('POST', $path, $handler);
    }

    public function dispatch($method, $uri) {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri)) {
                $params = $this->extractParams($route['path'], $uri);
                $handler = $route['handler'];
                
                if (is_array($handler)) {
                    list($controller, $action) = $handler;
                    $controller = new $controller();
                    return call_user_func_array([$controller, $action], $params);
                }
                
                return call_user_func_array($handler, $params);
            }
        }
        
        throw new \Exception("Route not found");
    }

    private function matchPath($routePath, $uri) {
        $routeRegex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        return preg_match("#^{$routeRegex}$#", $uri);
    }

    private function extractParams($routePath, $uri) {
        $params = [];
        $routeParts = explode('/', trim($routePath, '/'));
        $uriParts = explode('/', trim($uri, '/'));
        
        foreach ($routeParts as $i => $part) {
            if (preg_match('/\{([^}]+)\}/', $part, $matches)) {
                $params[] = $uriParts[$i];
            }
        }
        
        return $params;
    }
}
