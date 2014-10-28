<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface TermLookup {

	/**
	 * Gets the label of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getLabel( EntityId $entityId, $languageCode );

	/**
	 * Gets all labels of an Entity with the specified EntityId.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId );

	/**
	 * Gets the description of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getDescription( EntityId $entityId, $languageCode );

	/**
	 * Gets all descriptions of an Entity with the specified EntityId.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId );

}
