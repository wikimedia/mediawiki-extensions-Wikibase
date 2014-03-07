<?php

namespace Wikibase;

use OutOfBoundsException;
use Wikibase\EntityId;

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
	 * Returns null if the Entity does not have a label in the given language.
	 * Throws an OutOfBoundsException if an Entity with that ID does not exist.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException
	 * @return string|null
	 */
	public function getLabelForId( EntityId $entityId, $languageCode );

	/**
	 * Returns null if the Entity does not have a description in the given language.
	 * Throws an OutOfBoundsException if an Entity with that ID does not exist.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException
	 * @return string|null
	 */
	public function getDescriptionForId( EntityId $entityId, $languageCode );

	/**
	 * @param EntityId $entityId
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @throws OutOfBoundsException
	 * @return string[]|null Value array with a language code and the actual label.
	 */
	public function getLabelValueForId(
		EntityId $entityId,
		LanguageFallbackChain $languageFallbackChain
	);

	/**
	 * @param EntityId $entityId
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @throws OutOfBoundsException
	 * @return string[]|null Value array with a language code and the actual description.
	 */
	public function getDescriptionValueForId(
		EntityId $entityId,
		LanguageFallbackChain $languageFallbackChain
	);

}
