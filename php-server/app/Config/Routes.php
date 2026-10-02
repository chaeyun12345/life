<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setAutoRoute(false);
$routes->get('/', 'Dayflow::dashboard');
$routes->get('login','Dayflow::loginPage');
$routes->post('api/login','Dayflow::login');
$routes->post('api/logout','Dayflow::logout');
$routes->get('password','Dayflow::passwordPage');
$routes->post('api/password','Dayflow::password');
$routes->match(['get','put'],'api/data','Dayflow::data');
$routes->get('api/announcements','Dayflow::announcements');
$routes->get('admin','Dayflow::adminPage');
$routes->match(['get','post'],'api/admin/members','Dayflow::members');
$routes->put('api/admin/members/(:num)','Dayflow::member/$1');
$routes->match(['get','post'],'api/admin/announcements','Dayflow::adminAnnouncements');
$routes->match(['put','delete'],'api/admin/announcements/(:num)','Dayflow::adminAnnouncements/$1');
