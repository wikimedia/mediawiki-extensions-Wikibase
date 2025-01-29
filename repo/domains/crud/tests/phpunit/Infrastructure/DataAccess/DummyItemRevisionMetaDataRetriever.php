<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * Use this only as a filler when all that matters is that the requested Item exists.
 *
 * @license GPL-2.0-or-later
 */
class DummyItemRevisionMetaDataRetriever implements ItemRevisionMetadataRetriever {
	public function getLatestRevisionMetadata( ItemId $itemId ): LatestItemRevisionMetadataResult {
		return LatestItemRevisionMetadataResult::concreteRevision( rand(), date( 'YmdHis' ) );
	}
}
