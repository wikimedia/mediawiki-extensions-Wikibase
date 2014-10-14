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
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId );

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string|null
	 */
	public function getLabel( EntityId $entityId, $languageCode );

	/**
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId );

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string|null
	 */
	public function getDescription( EntityId $entityId, $languageCode );

}
