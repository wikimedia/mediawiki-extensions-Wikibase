<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyUpdater {

	public function update( Property $property, EditMetadata $editMetadata ): PropertyRevision;

}
