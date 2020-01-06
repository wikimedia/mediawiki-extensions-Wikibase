<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermFallback;

/**
 * A {@link LabelDescriptionLookup} that is guaranteed to return
 * {@link TermFallback}s, not merely {@link Term}s.
 *
 * @since 4.0.0
 *
 * @license GPL-2.0-or-later
 */
interface FallbackLabelDescriptionLookup extends LabelDescriptionLookup {

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId );

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getDescription( EntityId $entityId );

}
