<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Mikael Nordin <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Jupyter\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
  'resources' => [],
  'routes' => [
    ['name' => 'api#callback', 'url' => '/callback', 'verb' => 'GET'],
    ['name' => 'api#query', 'url' => '/query', 'verb' => 'GET'],
    ['name' => 'api#register', 'url' => '/register', 'verb' => 'POST'],
    ['name' => 'api#remove', 'url' => '/remove', 'verb' => 'POST'],
    ['name' => 'api#query_user', 'url' => '/query_user', 'verb' => 'GET'],
    ['name' => 'api#register_user', 'url' => '/register_user', 'verb' => 'POST'],
    ['name' => 'api#remove_user', 'url' => '/remove_user', 'verb' => 'POST']
  ]
];
