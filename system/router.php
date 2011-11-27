<?php

class Router {
    function __construct($routeTable) {
        if(is_array($routeTable)) {
            foreach($routeTable as $routeKey => $route) {
                $scanner = new RouteScanner($route->url);
                $parser = new RouteParser($scanner);
                $parsedRoute = $parser->parse();
                $parsedRoute->compile();
                echo '<pre>';
                $parser->displayErrors();
                echo '</pre><br />';
            }
        }
    }
}