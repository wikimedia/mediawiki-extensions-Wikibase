<?php
// ElasticSearch function for entity weight
// satu function is from includes/Search/RescoreBuilders.php in CirrusSearch
// The formula is: x^a/(k^a+x^a)
// NOTE: that satu is always 0.5 when x == k.
// See also: https://www.desmos.com/calculator/ahuzvkiqmi
return [
	'entity_weight' => [
		'score_mode' => 'sum',
		'functions' => [
			[
				// Incoming links: k = 50
				'type' => 'satu',
				'weight' => '0.6',
				'params' => [ 'field' => 'incoming_links', 'missing' => 0, 'a' => 2 , 'k' => 50 ]
			],
			[
				// Site links: k = 20
				'type' => 'satu',
				'weight' => '0.4',
				'params' => [ 'field' => 'sitelink_count', 'missing' => 0, 'a' => 2, 'k' => 20 ]
			],
		],
	],
	'entity_weight_boost' => [
		'score_mode' => 'sum',
		'functions' => [
			[
				// Incoming links: k = 50
				'type' => 'satu',
				'weight' => '0.6',
				'params' => [ 'field' => 'incoming_links', 'missing' => 0, 'a' => 2 , 'k' => 50 ]
			],
			[
				// Site links: k = 20
				'type' => 'satu',
				'weight' => '0.4',
				'params' => [ 'field' => 'sitelink_count', 'missing' => 0, 'a' => 2, 'k' => 20 ]
			],
			[
				// (De)boosting by statement values
				'type' => 'statement_boost',
				'weight' => '0.1',
			]
		],
	],
];
