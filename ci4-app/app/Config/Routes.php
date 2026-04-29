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
    $routes->get('payables-dashboard', 'PayablesDashboard::index');

    $routes->get('clients', 'Clients::index');
    $routes->get('clients/(:num)/soa', 'Clients::soaPrint/$1');
    $routes->post('clients', 'Clients::create');
    $routes->post('clients/(:num)', 'Clients::update/$1');
    $routes->post('clients/(:num)/delete', 'Clients::delete/$1');

    $routes->get('products', 'Products::index');
    $routes->post('products', 'Products::create');
    $routes->post('products/(:num)', 'Products::update/$1');
    $routes->post('products/(:num)/delete', 'Products::delete/$1');
    $routes->get('payables/products', 'Products::payablesIndex');
    $routes->post('payables/products', 'Products::createPayables');
    $routes->post('payables/products/(:num)', 'Products::updatePayables/$1');
    $routes->post('payables/products/(:num)/delete', 'Products::deletePayables/$1');

    $routes->get('banks', 'Banks::index', ['filter' => 'permission:banks.view']);
    $routes->post('banks', 'Banks::create', ['filter' => 'permission:banks.view']);
    $routes->post('banks/(:num)', 'Banks::update/$1', ['filter' => 'permission:banks.view']);
    $routes->post('banks/(:num)/delete', 'Banks::delete/$1', ['filter' => 'permission:banks.view']);
    $routes->get('payables/banks', 'Banks::payablesIndex', ['filter' => 'permission:banks.view']);
    $routes->post('payables/banks', 'Banks::createPayables', ['filter' => 'permission:banks.view']);
    $routes->post('payables/banks/(:num)', 'Banks::updatePayables/$1', ['filter' => 'permission:banks.view']);
    $routes->post('payables/banks/(:num)/delete', 'Banks::deletePayables/$1', ['filter' => 'permission:banks.view']);

    $routes->get('suppliers', 'Suppliers::index');
    $routes->post('suppliers', 'Suppliers::create');
    $routes->post('suppliers/(:num)', 'Suppliers::update/$1');
    $routes->post('suppliers/(:num)/delete', 'Suppliers::delete/$1');

    $routes->get('cashiers', 'Cashiers::index', ['filter' => 'permission:cashiers.view']);
    $routes->post('cashiers', 'Cashiers::create', ['filter' => 'permission:cashiers.view']);
    $routes->post('cashiers/(:num)', 'Cashiers::update/$1', ['filter' => 'permission:cashiers.view']);
    $routes->post('cashiers/(:num)/delete', 'Cashiers::delete/$1', ['filter' => 'permission:cashiers.view']);
    $routes->post('cashiers/assign-range', 'Cashiers::assignRange', ['filter' => 'permission:cashiers.view']);

    $routes->get('deliveries', 'Deliveries::index');
    $routes->get('deliveries/list', 'Deliveries::index');
    $routes->get('deliveries/print', 'Deliveries::print');
    $routes->get('deliveries/client/(:num)', 'Deliveries::createForm/$1');
    $routes->get('clients/(:num)/deliveries', 'Deliveries::clientList/$1');
    $routes->get('clients/(:num)/deliveries/print', 'Deliveries::listPrint/$1');
    $routes->post('deliveries', 'Deliveries::create');
    $routes->post('deliveries/(:num)', 'Deliveries::update/$1');
    $routes->post('deliveries/(:num)/void', 'Deliveries::void/$1');

    $routes->get('other-accounts', 'OtherAccounts::index');
    $routes->post('other-accounts', 'OtherAccounts::create');
    $routes->post('other-accounts/(:num)', 'OtherAccounts::update/$1');
    $routes->post('other-accounts/(:num)/delete', 'OtherAccounts::delete/$1');
    $routes->get('other-accounts/(:num)/get', 'OtherAccounts::getAccount/$1');

    $routes->get('ledger', 'Ledger::index');
    $routes->get('ledger/print', 'Ledger::print');

    $routes->get('payments', 'Payments::index');
    $routes->get('payments/print', 'Payments::print');
    $routes->get('payments/client/(:num)', 'Payments::clientList/$1');
    $routes->get('payments/client/(:num)/print', 'Payments::listPrint/$1');
    $routes->get('payments/client/(:num)/create', 'Payments::createForm/$1');
    $routes->post('payments', 'Payments::store');
    $routes->post('payments/quick-pay', 'Payments::quickPay');

    $routes->get('purchase-orders', 'PurchaseOrders::index');
    $routes->get('purchase-orders/print', 'PurchaseOrders::print');
    $routes->get('suppliers/(:num)/purchase-orders', 'PurchaseOrders::supplierList/$1');
    $routes->post('purchase-orders', 'PurchaseOrders::create');
    $routes->post('purchase-orders/(:num)', 'PurchaseOrders::update/$1');
    $routes->post('purchase-orders/(:num)/void', 'PurchaseOrders::void/$1');

    $routes->get('payable-ledger', 'PayableLedger::index');
    $routes->get('payable-ledger/print', 'PayableLedger::print');

    $routes->get('payables', 'Payables::index');
    $routes->get('payables/print', 'Payables::print');
    $routes->get('payables/supplier/(:num)', 'Payables::supplierList/$1');
    $routes->get('payables/supplier/(:num)/print', 'Payables::supplierPrint/$1');
    $routes->get('payables/supplier/(:num)/create', 'Payables::createForm/$1');
    $routes->post('payables', 'Payables::store');
    $routes->post('payables/quick-pay', 'Payables::quickPay');

    $routes->get('boa', 'Boa::index');
    $routes->get('boa/print', 'Boa::print');

    $routes->get('reports/credits', 'Reports::credits');
    $routes->get('reports/credits/print', 'Reports::creditsPrint');
    $routes->get('reports/overdue', 'Reports::overdue');
    $routes->get('reports/overdue/print', 'Reports::overduePrint');
    $routes->get('reports/voided', 'Reports::voided');
    $routes->get('reports/voided/print', 'Reports::voidedPrint');

    $routes->get('payable-reports/credits', 'PayableReports::credits');
    $routes->get('payable-reports/credits/print', 'PayableReports::creditsPrint');
    $routes->get('payable-reports/overdue', 'PayableReports::overdue');
    $routes->get('payable-reports/overdue/print', 'PayableReports::overduePrint');
    $routes->get('payable-reports/voided', 'PayableReports::voided');
    $routes->get('payable-reports/voided/print', 'PayableReports::voidedPrint');

    $routes->get('excess', 'Excess::index');
});
