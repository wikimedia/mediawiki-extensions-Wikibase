<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;

/**
 * @license GPL-2.0-or-later
 */
interface ItemsBatchRetriever {

	public function getItems( ItemId ...$ids ): ItemsBatch;

}
