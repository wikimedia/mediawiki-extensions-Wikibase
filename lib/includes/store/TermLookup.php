<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * A service interface for looking up entity terms.
 *
 * @note: A TermLookup cannot be used to determine whether an entity exists or not.
 *
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
	 * @throws OutOfBoundsException for label or entity not found
	 *
	 * @return string
	 * @throws OutOfBoundsException if no such label was found
	 */
	public function getLabel( EntityId $entityId, $languageCode );

	/**
	 * Gets all labels of an Entity with the specified EntityId.
	 *
	 * If $languages is given, the result will contain the entries for the
	 * requested languages, if they exist.
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The list of languages to fetch
	 *
	 * @throws OutOfBoundsException if the entity was not found (not guaranteed).
	 * @return string[] labels, keyed by language.
	 *         An empty array may or may not indicate that the entity does not exist.
	 */
	public function getLabels( EntityId $entityId, array $languageCodes );

	/**
	 * Gets the description of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException for description or entity not found
	 * @return string
	 */
	public function getDescription( EntityId $entityId, $languageCode );

	/**
	 * Gets all descriptions of an Entity with the specified EntityId.
	 *
	 * If $languages is given, the result will contain the entries for the
	 * requested languages, if they exist.
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The list of languages to fetch
	 *
	 * @throws OutOfBoundsException if the entity was not found (not guaranteed).
	 * @return string[] descriptions, keyed by language.
	 *         An empty array may or may not indicate that the entity does not exist.
	 */
	public function getDescriptions( EntityId $entityId, array $languageCodes );

}
