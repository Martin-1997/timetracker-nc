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

        // Legacy /ajax/* routes — served by AjaxController, kept for GUI backward compat
        ['name' => 'ajax#start_timer',          'url' => '/ajax/start-timer/{name}',          'verb' => 'POST'],
        ['name' => 'ajax#stop_timer',           'url' => '/ajax/stop-timer/{name}',           'verb' => 'POST'],
        ['name' => 'ajax#index',                'url' => '/ajax/',                             'verb' => 'GET'],
        ['name' => 'ajax#work_intervals',       'url' => '/ajax/work-intervals',               'verb' => 'GET'],
        ['name' => 'ajax#update_work_interval', 'url' => '/ajax/update-work-interval/{id}',    'verb' => 'POST'],
        ['name' => 'ajax#add_work_interval',    'url' => '/ajax/add-work-interval/{name}',     'verb' => 'POST'],
        ['name' => 'ajax#delete_work_interval', 'url' => '/ajax/delete-work-interval/{id}',    'verb' => 'POST'],
        ['name' => 'ajax#add_cost',             'url' => '/ajax/add-cost/{id}',                'verb' => 'POST'],
        ['name' => 'ajax#get_clients',          'url' => '/ajax/clients',                      'verb' => 'GET'],
        ['name' => 'ajax#add_client',           'url' => '/ajax/add-client/{name}',            'verb' => 'POST'],
        ['name' => 'ajax#edit_client',          'url' => '/ajax/edit-client/{id}',             'verb' => 'POST'],
        ['name' => 'ajax#delete_client',        'url' => '/ajax/delete-client/{id}',           'verb' => 'POST'],
        ['name' => 'ajax#get_projects',         'url' => '/ajax/projects',                     'verb' => 'GET'],
        ['name' => 'ajax#get_projects_table',   'url' => '/ajax/projects-table',               'verb' => 'GET'],
        ['name' => 'ajax#add_project',          'url' => '/ajax/add-project/{name}',           'verb' => 'POST'],
        ['name' => 'ajax#edit_project',         'url' => '/ajax/edit-project/{id}',            'verb' => 'POST'],
        ['name' => 'ajax#delete_project',       'url' => '/ajax/delete-project/{id}',          'verb' => 'POST'],
        ['name' => 'ajax#delete_project_with_data', 'url' => '/ajax/delete-project-with-data/{id}', 'verb' => 'POST'],
        ['name' => 'ajax#get_tags',             'url' => '/ajax/tags',                         'verb' => 'GET'],
        ['name' => 'ajax#add_tag',              'url' => '/ajax/add-tag/{name}',               'verb' => 'POST'],
        ['name' => 'ajax#edit_tag',             'url' => '/ajax/edit-tag/{id}',                'verb' => 'POST'],
        ['name' => 'ajax#delete_tag',           'url' => '/ajax/delete-tag/{id}',              'verb' => 'POST'],
        ['name' => 'ajax#get_goals',            'url' => '/ajax/goals',                        'verb' => 'GET'],
        ['name' => 'ajax#add_goal',             'url' => '/ajax/add-goal',                     'verb' => 'POST'],
        ['name' => 'ajax#delete_goal',          'url' => '/ajax/delete-goal/{id}',             'verb' => 'POST'],
        ['name' => 'ajax#get_report',           'url' => '/ajax/report',                       'verb' => 'GET'],
        ['name' => 'ajax#post_timeline',        'url' => '/ajax/timeline',                     'verb' => 'POST'],
        ['name' => 'ajax#get_timelines',        'url' => '/ajax/timelines',                    'verb' => 'GET'],
        ['name' => 'ajax#get_timelines_admin',  'url' => '/ajax/timelines-admin',              'verb' => 'GET'],
        ['name' => 'ajax#download_timeline',    'url' => '/ajax/download-timeline/{id}',       'verb' => 'GET'],
        ['name' => 'ajax#edit_timeline',        'url' => '/ajax/edit-timeline/{id}',           'verb' => 'POST'],
        ['name' => 'ajax#delete_timeline',      'url' => '/ajax/delete-timeline/{id}',         'verb' => 'POST'],
        ['name' => 'ajax#email_timeline',       'url' => '/ajax/email-timeline/{id}',          'verb' => 'POST'],

        // REST /api/v1/* routes — served by domain controllers, documented in api/openapi.yaml
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
