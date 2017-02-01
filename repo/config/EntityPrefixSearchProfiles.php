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
		'any' => 1,
		'lang-exact' => 40,
		'lang-folded' => 30,
		'lang-prefix' => 15,
		'fallback-exact' => 25,
		'fallback-folded' => 20,
		'fallback-prefix' => 10,
		'fallback-discount' => 0.9,
	]
];
