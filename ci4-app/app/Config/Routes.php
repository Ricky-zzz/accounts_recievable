<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('logout', 'Auth::logout');

$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');

    $routes->get('clients', 'Clients::index');
    $routes->get('clients/new', 'Clients::createForm');
    $routes->post('clients', 'Clients::create');
    $routes->get('clients/(:num)/edit', 'Clients::edit/$1');
    $routes->post('clients/(:num)', 'Clients::update/$1');
    $routes->post('clients/(:num)/delete', 'Clients::delete/$1');

    $routes->get('products', 'Products::index');
    $routes->get('products/new', 'Products::createForm');
    $routes->post('products', 'Products::create');
    $routes->get('products/(:num)/edit', 'Products::edit/$1');
    $routes->post('products/(:num)', 'Products::update/$1');
    $routes->post('products/(:num)/delete', 'Products::delete/$1');

    $routes->get('banks', 'Banks::index');
    $routes->get('banks/new', 'Banks::createForm');
    $routes->post('banks', 'Banks::create');
    $routes->get('banks/(:num)/edit', 'Banks::edit/$1');
    $routes->post('banks/(:num)', 'Banks::update/$1');
    $routes->post('banks/(:num)/delete', 'Banks::delete/$1');

    $routes->get('cashiers', 'Cashiers::index');
    $routes->post('cashiers', 'Cashiers::create');
    $routes->post('cashiers/assign-range', 'Cashiers::assignRange');

    $routes->get('deliveries', 'Deliveries::index');
    $routes->get('deliveries/new', 'Deliveries::createForm');
    $routes->post('deliveries', 'Deliveries::create');

    $routes->get('ledger', 'Ledger::index');

    $routes->get('payments', 'Payments::index');
    $routes->get('payments/client/(:num)', 'Payments::createForm/$1');
    $routes->post('payments', 'Payments::store');

    $routes->get('boa', 'Boa::index');

    $routes->get('excess', 'Excess::index');
});
