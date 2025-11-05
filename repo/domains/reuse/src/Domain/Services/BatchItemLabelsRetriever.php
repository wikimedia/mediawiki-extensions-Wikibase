<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;

/**
 * @license GPL-2.0-or-later
 */
interface BatchItemLabelsRetriever {

	/**
	 * @param ItemId[] $itemIds
	 * @param string[] $languageCodes
	 *
	 * @return ItemLabelsBatch
	 */
	public function getItemLabels( array $itemIds, array $languageCodes ): ItemLabelsBatch;

}
