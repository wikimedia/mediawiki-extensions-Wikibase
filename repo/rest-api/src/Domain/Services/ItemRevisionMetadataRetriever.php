<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;

/**
 * @license GPL-2.0-or-later
 */
interface ItemRevisionMetadataRetriever {

	public function getLatestRevisionMetadata( ItemId $itemId ): LatestItemRevisionMetadataResult;

}
