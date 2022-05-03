<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetaDataResult;

/**
 * @license GPL-2.0-or-later
 */
interface ItemRevisionMetaDataRetriever {

	public function getLatestRevisionMetaData( ItemId $itemId ): LatestItemRevisionMetaDataResult;

}
