<?php

return [

	'connections' => [

		'api'     => [
			'driver'  => 'iron',
			'host'    => 'mq-aws-us-east-1.iron.io',
			'token'   => '#irontoken#',
			'project' => '#ironkey#',
			'queue'   => 'api-#confid#',
		],

		'gateway' => [
			'driver'  => 'iron',
			'host'    => 'mq-aws-us-east-1.iron.io',
			'token'   => '#irontoken#',
			'project' => '#ironkey#',
			'queue'   => 'gateway-#confid#',
		],

	],

];