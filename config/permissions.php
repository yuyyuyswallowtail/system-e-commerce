<?php

return [

    'permissions' => [
        'users.read' => 'View users',
        'users.create' => 'Create users',
        'users.update' => 'Update users',
        'users.delete' => 'Soft-delete users',
        'users.restore' => 'Restore deleted users',
        'users.purge' => 'Permanently delete users',
        'users.status' => 'Change user status',
        'users.roles' => 'Assign roles to users',
        'roles.read' => 'View roles',
        'roles.manage' => 'Manage roles',
        'permissions.read' => 'View permissions',
        'permissions.manage' => 'Assign permissions to roles',
        'audit.read' => 'View the audit log',
        'sessions.read' => 'View sessions',
        'sessions.revoke' => 'Revoke sessions',
        'presence.read' => 'View online users',
        'products.read' => 'View products in admin',
        'products.create' => 'Create products',
        'products.update' => 'Update products',
        'products.delete' => 'Soft-delete products',
        'products.restore' => 'Restore deleted products',
        'categories.manage' => 'Manage product categories',
        'stock.read' => 'View stock',
        'stock.adjust' => 'Adjust stock',
        'carts.read' => 'View carts in admin',
        'payments.read' => 'View payments',
        'payments.refund' => 'Refund payments',
        'orders.read' => 'View all orders',
        'orders.manage' => 'Change order status',
        'history.read' => 'View the history of all users',
        'articles.read' => 'View articles in admin',
        'articles.create' => 'Create articles',
        'articles.update' => 'Update articles',
        'articles.publish' => 'Publish and unpublish articles',
        'articles.delete' => 'Soft-delete articles',
        'articles.restore' => 'Restore deleted articles',
        'comments.moderate' => 'Moderate comments',
        'dashboard.read' => 'View the admin dashboard',
    ],

    // '*' grants every permission. 'all_except' grants every permission but the listed codes.
    'roles' => [
        'super_admin' => '*',
        'admin' => ['all_except' => ['permissions.manage']],
        'member' => [],
    ],

];
