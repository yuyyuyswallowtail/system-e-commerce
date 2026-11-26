<?php

// Per list key: columns the list may search, filter and sort by. Anything else is ignored.
return [

    'users' => [
        'searchable' => ['name', 'email'],
        'filterable' => ['status'],
        'sortable' => ['name', 'email', 'status', 'created_at'],
        'default_sort' => ['created_at', 'desc'],
    ],

    'products' => [
        'searchable' => ['name', 'sku'],
        'filterable' => ['status', 'category_id'],
        'sortable' => ['name', 'sku', 'price_amount', 'status', 'created_at'],
        'default_sort' => ['created_at', 'desc'],
    ],

];
