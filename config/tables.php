<?php

// Per list key: columns the list may search, filter and sort by. Anything else is ignored.
return [

    'users' => [
        'searchable' => ['name', 'email'],
        'filterable' => ['status'],
        'sortable' => ['name', 'email', 'status', 'created_at'],
        'default_sort' => ['created_at', 'desc'],
    ],

    'roles' => [
        'searchable' => ['name'],
        'filterable' => ['guard_name'],
        'sortable' => ['name', 'created_at'],
        'default_sort' => ['name', 'asc'],
    ],

    'sessions' => [
        'searchable' => ['users.name', 'users.email', 'sessions.ip_address'],
        'filterable' => ['users.status'],
        'sortable' => ['users.name', 'sessions.ip_address', 'sessions.last_activity'],
        'default_sort' => ['sessions.last_activity', 'desc'],
    ],

    'audit' => [
        'searchable' => ['description', 'event', 'log_name'],
        'filterable' => ['event', 'log_name'],
        'sortable' => ['created_at', 'event', 'log_name'],
        'default_sort' => ['created_at', 'desc'],
    ],

    'products' => [
        'searchable' => ['name', 'sku'],
        'filterable' => ['status', 'category_id'],
        'sortable' => ['name', 'sku', 'price_amount', 'status', 'created_at'],
        'default_sort' => ['created_at', 'desc'],
    ],

];
