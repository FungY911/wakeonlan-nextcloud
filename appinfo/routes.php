<?php
declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index',     'url' => '/app',                 'verb' => 'GET'],
		['name' => 'wol#add',        'url' => '/device/add',          'verb' => 'POST'],
		['name' => 'wol#wake',       'url' => '/device/{id}/wake',    'verb' => 'POST', 'requirements' => ['id' => '\d+']],
		['name' => 'wol#delete',     'url' => '/device/{id}/delete',  'verb' => 'POST', 'requirements' => ['id' => '\d+']],

    ['name' => 'wol#status', 'url' => '/device/status', 'verb' => 'GET'],
	],
];
