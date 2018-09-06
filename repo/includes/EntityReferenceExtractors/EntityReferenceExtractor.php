<?php

namespace Wikibase\Repo\EntityReferenceExtractors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Describes objects that extract ids of referenced entities from an entity.
 * Can be used to determine which other entities an entity links to (e.g. in "What links here").
 *
 * @license GPL-2.0-or-later
 */
interface EntityReferenceExtractor {

	/**
	 * @param EntityDocument $entity
	 * @return EntityId[] There is no guarantee that array elements will be unique.
	 */
	public function extractEntityIds( EntityDocument $entity );

}
