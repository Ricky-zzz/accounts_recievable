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
    $routes->post('clients', 'Clients::create');
    $routes->post('clients/(:num)', 'Clients::update/$1');
    $routes->post('clients/(:num)/delete', 'Clients::delete/$1');

    $routes->get('products', 'Products::index');
    $routes->post('products', 'Products::create');
    $routes->post('products/(:num)', 'Products::update/$1');
    $routes->post('products/(:num)/delete', 'Products::delete/$1');

    $routes->get('banks', 'Banks::index');
    $routes->post('banks', 'Banks::create');
    $routes->post('banks/(:num)', 'Banks::update/$1');
    $routes->post('banks/(:num)/delete', 'Banks::delete/$1');

    $routes->get('cashiers', 'Cashiers::index');
    $routes->post('cashiers', 'Cashiers::create');
    $routes->post('cashiers/assign-range', 'Cashiers::assignRange');

    $routes->get('deliveries', 'Deliveries::index');
    $routes->get('deliveries/list', 'Deliveries::index');
    $routes->get('deliveries/client/(:num)', 'Deliveries::createForm/$1');
    $routes->get('clients/(:num)/deliveries', 'Deliveries::clientList/$1');
    $routes->get('clients/(:num)/deliveries/print', 'Deliveries::listPrint/$1');
    $routes->post('deliveries', 'Deliveries::create');

    $routes->get('other-accounts', 'OtherAccounts::index');
    $routes->post('other-accounts', 'OtherAccounts::create');
    $routes->post('other-accounts/(:num)', 'OtherAccounts::update/$1');
    $routes->post('other-accounts/(:num)/delete', 'OtherAccounts::delete/$1');
    $routes->get('other-accounts/(:num)/get', 'OtherAccounts::getAccount/$1');

    $routes->get('ledger', 'Ledger::index');
    $routes->get('ledger/print', 'Ledger::print');

    $routes->get('payments', 'Payments::index');
    $routes->get('payments/client/(:num)', 'Payments::clientList/$1');
    $routes->get('payments/client/(:num)/print', 'Payments::listPrint/$1');
    $routes->get('payments/client/(:num)/create', 'Payments::createForm/$1');
    $routes->post('payments', 'Payments::store');

    $routes->get('boa', 'Boa::index');
    $routes->get('boa/print', 'Boa::print');

    $routes->get('excess', 'Excess::index');
});
