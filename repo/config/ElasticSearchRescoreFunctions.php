<?php
// ElasticSearch function for entity weight
// TODO: this will be amended with better criteria after we ensure the implementation works.
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
