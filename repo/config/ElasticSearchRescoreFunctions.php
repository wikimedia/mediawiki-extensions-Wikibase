<?php
// ElasticSearch function for entity weight
return [
	'entity_weight' => [
		'score_mode' => 'sum',
		'functions' => [
			[
				'type' => 'satu',
				'weight' => '1.2',
				'params' => [ 'field' => 'incoming_links', 'missing' => 0, 'a' => 2 , 'k' => 50 ]
			],
			[
				'type' => 'satu',
				'params' => [ 'field' => 'sitelink_count', 'missing' => 0, 'a' => 2, 'k' => 20 ]
			],
		],
	],
];
