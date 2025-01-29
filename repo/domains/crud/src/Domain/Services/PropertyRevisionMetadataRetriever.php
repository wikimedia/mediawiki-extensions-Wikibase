<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\LatestPropertyRevisionMetadataResult;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyRevisionMetadataRetriever {

	public function getLatestRevisionMetadata( NumericPropertyId $propertyId ): LatestPropertyRevisionMetadataResult;

}
