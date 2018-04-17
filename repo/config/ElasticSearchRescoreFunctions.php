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
				// Incoming links: k = 100, since it is normal to have a bunch of incoming links
				'type' => 'satu',
				'weight' => '0.6',
				'params' => [ 'field' => 'incoming_links', 'missing' => 0, 'a' => 1, 'k' => 100 ]
			],
			[
				// Site links: k = 20, tens of sites is a lot
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
				// Incoming links: k = 100, since it is normal to have a bunch of incoming links
				'type' => 'satu',
				'weight' => '0.6',
				'params' => [ 'field' => 'incoming_links', 'missing' => 0, 'a' => 1 , 'k' => 100 ]
			],
			[
				// Site links: k = 20, tens of sites is a lot
				'type' => 'satu',
				'weight' => '0.4',
				'params' => [ 'field' => 'sitelink_count', 'missing' => 0, 'a' => 2, 'k' => 20 ]
			],
			[
				// (De)boosting by statement values, see statementBoost in search settings
				'type' => 'term_boost',
				'weight' => '0.1',
				'params' => [
					'statement_keywords' => '_statementBoost_'
				]
			]
		],
	],
];
