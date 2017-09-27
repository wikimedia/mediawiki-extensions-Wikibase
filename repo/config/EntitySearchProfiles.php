<?php
// Search profiles for fulltext search
// Matches the syntax of Cirrus search profiles, e.g. in FullTextQueryBuilderProfiles.config.php
// Note that these will be merged with Cirrus standard profiles,
// so prefixing with 'wikibase' is recommended.
return [
	'wikibase' => [
		'builder_class' => \Wikibase\Repo\Search\Elastic\EntityFullTextQueryBuilder::class,
		'settings' => [
			'any' => 0.001,
			'lang-exact' => 2,
			'lang-folded' => 1.8,
			'lang-partial' => 1.8,
			'lang-prefix' => 1.1,
			'fallback-exact' => 1,
			'fallback-folded' => 0.9,
			'fallback-partial' => 0.9,
			'fallback-prefix' => 0.8,
			'fallback-discount' => 0.1,
		]
	],
];
