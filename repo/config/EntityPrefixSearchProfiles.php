<?php

/**
 * Profile defining weights for query matching options.
 * any - match in any language
 * lang-exact - exact match in specific language
 * lang-folded - casefolded/asciifolded match in specific language
 * lang-prefix - prefix match in specific language
 * fallback-exact - exact match in fallback language
 * fallback-folded - casefolded/asciifolded match in fallback language
 * fallback-prefix - prefix match in fallback language
 * fallback-discount - multiplier for each following fallback
 */
return [
	// FIXME: right now these weights are completely arbitrary. We need
	// to do some work to validate them.
	'default' => [
		'any' => 0.001,
		'lang-exact' => 2,
		'lang-folded' => 1.8,
		'lang-prefix' => 1.1,
		'fallback-exact' => 1,
		'fallback-folded' => 0.9,
		'fallback-prefix' => 0.8,
		'fallback-discount' => 0.1,
	]
];
