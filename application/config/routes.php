<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Admin routes
$route['admin'] = 'dashboard';
$route['admin/login'] = 'auth/login';
$route['admin/logout'] = 'auth/logout';
$route['admin/dashboard'] = 'dashboard';
$route['admin/questionnaires'] = 'questionnaires';
$route['admin/questionnaires/create'] = 'questionnaires/create';
$route['admin/questionnaires/edit/(:num)'] = 'questionnaires/edit/$1';
$route['admin/questionnaires/duplicate/(:num)'] = 'questionnaires/duplicate/$1';
$route['admin/responses'] = 'responses';
$route['admin/responses/view/(:num)'] = 'responses/view/$1';
$route['admin/responses/export'] = 'responses/export';
$route['admin/users'] = 'users';
$route['admin/reports'] = 'reports';
$route['admin/settings'] = 'settings';

// API routes
$route['api/auth/login'] = 'api/auth/login';
$route['api/auth/verify'] = 'api/auth/verify';
$route['api/questionnaires'] = 'api/questionnaires/index';
$route['api/forms/submit'] = 'api/forms/submit';
$route['api/photos/upload'] = 'api/photos/upload';