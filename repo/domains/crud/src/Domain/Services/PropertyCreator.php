<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyRevision;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyCreator {
	public function create( Property $property, EditMetadata $editMetadata ): PropertyRevision;
}
