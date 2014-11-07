<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\StorageException;

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
	 * @throws OutOfBoundsException for label not found
	 * @throws StorageException for Entity not found
	 * @return string
	 * @throws OutOfBoundsException if no such label was found
	 */
	public function getLabel( EntityId $entityId, $languageCode );

	/**
	 * Gets all labels of an Entity with the specified EntityId.
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException for Entity not found
	 * @return string[] labels, keyed by language.
	 */
	public function getLabels( EntityId $entityId );

	/**
	 * Gets the description of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException for description not found
	 * @throws StorageException for Entity not found
	 * @return string
	 * @throws OutOfBoundsException if no such description was found
	 */
	public function getDescription( EntityId $entityId, $languageCode );

	/**
	 * Gets all descriptions of an Entity with the specified EntityId.
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException for Entity not found
	 * @return string[] descriptions, keyed by language.
	 */
	public function getDescriptions( EntityId $entityId );

}
