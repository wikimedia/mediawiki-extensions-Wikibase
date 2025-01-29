<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\LatestItemRevisionMetadataResult;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemRevisionMetadataRetriever;

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
