<?php
return [
    'routes' => [
        // SPA page routes
        ['name' => 'page#index',           'url' => '/',               'verb' => 'GET'],
        ['name' => 'page#do_echo',         'url' => '/echo',           'verb' => 'POST'],
        ['name' => 'clients#index',        'url' => '/clients',        'verb' => 'GET'],
        ['name' => 'projects#index',       'url' => '/projects',       'verb' => 'GET'],
        ['name' => 'dashboard#index',      'url' => '/dashboard',      'verb' => 'GET'],
        ['name' => 'reports#index',        'url' => '/reports',        'verb' => 'GET'],
        ['name' => 'timelines#index',      'url' => '/timelines',      'verb' => 'GET'],
        ['name' => 'timelinesAdmin#index', 'url' => '/timelines-admin','verb' => 'GET'],
        ['name' => 'tags#index',           'url' => '/tags',           'verb' => 'GET'],
        ['name' => 'goals#index',          'url' => '/goals',          'verb' => 'GET'],

        // Work intervals & timer
        ['name' => 'timer#index',   'url' => '/api/v1/work-intervals',      'verb' => 'GET'],
        ['name' => 'timer#create',  'url' => '/api/v1/work-intervals',      'verb' => 'POST'],
        ['name' => 'timer#update',  'url' => '/api/v1/work-intervals/{id}', 'verb' => 'PUT'],
        ['name' => 'timer#destroy', 'url' => '/api/v1/work-intervals/{id}', 'verb' => 'DELETE'],
        ['name' => 'timer#start',   'url' => '/api/v1/timer/start',         'verb' => 'POST'],
        ['name' => 'timer#stop',    'url' => '/api/v1/timer/stop',          'verb' => 'POST'],

        // Clients
        ['name' => 'client#index',   'url' => '/api/v1/clients',      'verb' => 'GET'],
        ['name' => 'client#create',  'url' => '/api/v1/clients',      'verb' => 'POST'],
        ['name' => 'client#update',  'url' => '/api/v1/clients/{id}', 'verb' => 'PUT'],
        ['name' => 'client#destroy', 'url' => '/api/v1/clients/{id}', 'verb' => 'DELETE'],

        // Projects
        ['name' => 'project#index',   'url' => '/api/v1/projects',      'verb' => 'GET'],
        ['name' => 'project#create',  'url' => '/api/v1/projects',      'verb' => 'POST'],
        ['name' => 'project#update',  'url' => '/api/v1/projects/{id}', 'verb' => 'PUT'],
        ['name' => 'project#destroy', 'url' => '/api/v1/projects/{id}', 'verb' => 'DELETE'],

        // Tags
        ['name' => 'tag#index',   'url' => '/api/v1/tags',      'verb' => 'GET'],
        ['name' => 'tag#create',  'url' => '/api/v1/tags',      'verb' => 'POST'],
        ['name' => 'tag#update',  'url' => '/api/v1/tags/{id}', 'verb' => 'PUT'],
        ['name' => 'tag#destroy', 'url' => '/api/v1/tags/{id}', 'verb' => 'DELETE'],

        // Goals
        ['name' => 'goal#index',   'url' => '/api/v1/goals',      'verb' => 'GET'],
        ['name' => 'goal#create',  'url' => '/api/v1/goals',      'verb' => 'POST'],
        ['name' => 'goal#destroy', 'url' => '/api/v1/goals/{id}', 'verb' => 'DELETE'],

        // Reports
        ['name' => 'report#index', 'url' => '/api/v1/report', 'verb' => 'GET'],

        // Timelines
        ['name' => 'timeline#index',       'url' => '/api/v1/timelines',              'verb' => 'GET'],
        ['name' => 'timeline#admin_index', 'url' => '/api/v1/timelines-admin',        'verb' => 'GET'],
        ['name' => 'timeline#create',      'url' => '/api/v1/timelines',              'verb' => 'POST'],
        ['name' => 'timeline#update',      'url' => '/api/v1/timelines/{id}',         'verb' => 'PUT'],
        ['name' => 'timeline#destroy',     'url' => '/api/v1/timelines/{id}',         'verb' => 'DELETE'],
        ['name' => 'timeline#download',    'url' => '/api/v1/timelines/{id}/download','verb' => 'GET'],
        ['name' => 'timeline#email',       'url' => '/api/v1/timelines/{id}/email',   'verb' => 'POST'],
    ]
];
