<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
interface ItemLabelsRetriever {

	public function getLabels( ItemId $itemId ): ?TermList;

}
