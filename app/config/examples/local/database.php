<?php
return [

	'connections' => [

		'mysql' => [
			'driver'    => 'mysql',
			'host'      => 'localhost',
			'database' => 'forge',
			'username' => 'forge',
			'password' => '#dbpass#',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'   => 'api_#confid#_',
		],

	],

];