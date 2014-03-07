<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;

/**
 * TODO: Implement a TermIndexTermLookup that gets its information from a TermIndex.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
interface EntityTermLookup {

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException if an Entity with that ID does not exist.
	 * @return string|null Label or null if the Entity does not have a label in the
	 * given language.
	 */
	public function getLabelForId( EntityId $entityId, $languageCode );

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException if an Entity with that ID does not exist.
	 * @return string|null Description or null if the Entity does not have a description in the
	 * given language.
	 */
	public function getDescriptionForId( EntityId $entityId, $languageCode );

	/**
	 * @param EntityId $entityId
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @throws OutOfBoundsException if an Entity with that ID does not exist.
	 * @return string[]|null array( 'language' => $languageCode, 'value' => $label ) or null
	 * if the Entity does not have a label in the given language.
	 */
	public function getLabelValueForId(
		EntityId $entityId,
		LanguageFallbackChain $languageFallbackChain
	);

	/**
	 * @param EntityId $entityId
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @throws OutOfBoundsException if an Entity with that ID does not exist.
	 * @return string[]|null array( 'language' => $languageCode, 'value' => $description ) or null
	 * if the Entity does not have a description in the given language.
	 */
	public function getDescriptionValueForId(
		EntityId $entityId,
		LanguageFallbackChain $languageFallbackChain
	);

}
