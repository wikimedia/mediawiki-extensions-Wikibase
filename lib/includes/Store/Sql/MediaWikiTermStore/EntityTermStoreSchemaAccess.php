<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Accessor to entity terms stored in a relational db.
 */
interface EntityTermStoreSchemaAccess {

	/**
	 * set terms for an entity in entity term store
	 *
	 * @param EntityId $entityId
	 * @param array $termsArray array containing terms per type per language.
	 *  Example:
	 * 	[
	 *		'label' => [
	 *			'en' => 'some label',
	 *			'de' => 'another label',
	 *			...
	 *		],
	 *		'alias' => [
	 *			'en' => [ 'alias', 'another alias', ...],
	 *			'de' => 'de alias',
	 *			...
	 *		],
	 *		...
	 *  ]
	 */
	public function setTerms( EntityId $entityId, array $termsArray );

	/**
	 * clear terms of any type or language linked to an entity
	 *
	 * @param EntityId $entityId
	 */
	public function unsetTerms( EntityId $entityId );

}
