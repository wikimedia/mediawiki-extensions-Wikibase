<?php

/**
 * Definition of entity service overrides for Federated Properties.
 * The array returned by the code below is supposed to be merged with the content of
 * repo/WikibaseRepo.entitytypes.php.
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @see docs/entiytypes.wiki
 *
 * @license GPL-2.0-or-later
 */
return [
	'property' => [
		'article-id-lookup-callback' => function () {
			return new \Wikibase\Lib\Store\EntityArticleIdNullLookup();
		},
	]
];
