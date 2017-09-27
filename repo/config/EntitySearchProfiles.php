<?php
// Search profiles for fulltext search
// Matches the syntax of Cirrus search profiles, e.g. in FullTextQueryBuilderProfiles.config.php
// Note that these will be merged with Cirrus standard profiles,
// so prefixing with 'wikibase' is recommended.
return [
	'wikibase' => [
		'builder_class' => \Wikibase\Repo\Search\Elastic\EntityFullTextQueryBuilder::class,
		'settings' => [
			'any'               => 0.0001,
			'lang-exact'        => 1,
			'lang-folded'       => 0.01,
			'lang-partial'      => 0.01,
			'lang-prefix'       => 0.006,
			'fallback-exact'    => 0.005,
			'fallback-folded'   => 0.003,
			'fallback-partial'  => 0.003,
			'fallback-prefix'   => 0.002,
			'fallback-discount' => 0.1,
		]
	],
];
