<?php
// ElasticSearch function for entity weight
return [
	'entity_weight' => [
		'score_mode' => 'max',
		'functions' => [
			[
				'type' => 'custom_field',
				'params' => [ 'field' => 'label_count', 'missing' => 0 ]
			],
			[
				'type' => 'custom_field',
				'params' => [ 'field' => 'sitelink_count', 'missing' => 0 ]
			],
		],
	],
];
