<?php

/**
 * Profile defining weights for query matching options.
 * any - match in any language
 * lang-exact - exact match in specific language
 * lang-folded - casefolded/asciifolded match in specific language
 * lang-prefix - prefix match in specific language
 * space-discount - how much we discount the match for matching without trailing space
 * fallback-exact - exact match in fallback language
 * fallback-folded - casefolded/asciifolded match in fallback language
 * fallback-prefix - prefix match in fallback language
 * fallback-discount - multiplier for each following fallback
 */
return [
	// FIXME: manually tuned, next step is to put in place a golden corpus of
	// graded queries and provide metrics to evaluate the quality objectively.
	'default' => [
		'any' => 0.001,
		'lang-exact' => 2,
		'lang-folded' => 1.6,
		'lang-prefix' => 1.1,
		'space-discount' => 0.8,
		'fallback-exact' => 1.9,
		'fallback-folded' => 1.3,
		'fallback-prefix' => 0.4,
		'fallback-discount' => 0.9,
	]
];
