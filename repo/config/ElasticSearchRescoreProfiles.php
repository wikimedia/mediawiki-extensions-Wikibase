<?php
// Wikibase prefix search scoring profile for CirrusSearch.
// This profile applies to the whole document.
// These configurations define how the results are ordered.
// The names should be distinct from other Cirrus rescoring profile, so
// prefixing with 'wikibase' is recommended.
return [
	// Default rescore profile
	'wikibase' => [
		'i18n_msg' => 'wikibase-rescore-profile-fulltext',
		'supported_namespaces' => 'all',
		'rescore' => [
			[
				'window' => 8192,
				'window_size_override' => 'EntitySearchRescoreWindowSize',
				'query_weight' => 1.0,
				'rescore_query_weight' => 1.0,
				'score_mode' => 'total',
				'type' => 'function_score',
				'function_chain' => 'entity_weight'
			],
		]
	]
];
