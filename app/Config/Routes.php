<?php

use CodeIgniter\Router\RouteCollection;
use Config\Services;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', '/dashboard');
$routes->get('/dashboard', 'DashboardController::index');
$routes->get('/login', 'SsoController::index');
$routes->get('/redirect', 'SsoController::getLogin');
$routes->get('/callback', 'SsoController::getCallback');
$routes->get('/connect', 'SsoController::getConnect');
$routes->get('/logout', 'SsoController::logout');

