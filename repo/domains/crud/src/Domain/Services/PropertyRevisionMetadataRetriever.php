<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestPropertyRevisionMetadataResult;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyRevisionMetadataRetriever {

	public function getLatestRevisionMetadata( NumericPropertyId $propertyId ): LatestPropertyRevisionMetadataResult;

}
