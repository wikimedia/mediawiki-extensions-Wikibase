<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemDescriptionsBatch;

/**
 * @license GPL-2.0-or-later
 */
interface BatchItemDescriptionsRetriever {

	/**
	 * @param ItemId[] $itemIds
	 * @param string[] $languageCodes
	 *
	 * @return ItemDescriptionsBatch
	 */
	public function getItemDescriptions( array $itemIds, array $languageCodes ): ItemDescriptionsBatch;

}
