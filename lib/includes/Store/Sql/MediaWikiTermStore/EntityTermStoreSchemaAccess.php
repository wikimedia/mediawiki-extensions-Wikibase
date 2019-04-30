<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

use Wikibase\DataModel\Entity\EntityId;

interface EntityTermStoreSchemaAccess {

	/**
	 * set terms for an entity in entity term store
	 * @param string $entityType
	 * @param EntityId $entityId
	 * @param array $termsArray array containing terms per type per language:
	 * 	[
	 *		'type' => [
	 *			[ 'language' => 'term' | [ 'term1', 'term2', ... ] ], ...
	 *		], ...
	 *  ]
	 */
	public function setTerms( $entityType, EntityId $entityId, array $termsArray );

	/**
	 * clear terms of any type or language on a property
	 *
	 * @param string $entityType
	 * @param EntityId $entityId
	 */
	public function unsetTerms( $entityType, EntityId $entityId );

}
