<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use LogicException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property as ReadModelProperty;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class InMemoryPropertyRepository implements PropertyRetriever, PropertyUpdater {

	private array $properties = [];
	private array $latestRevisionData = [];

	public function addProperty( Property $property ): void {
		if ( !$property->getId() ) {
			throw new LogicException( 'Test property must have an ID.' );
		}

		$this->properties[$property->getId()->getSerialization()] = $property;
	}

	public function getLatestRevisionId( PropertyId $id ): int {
		return $this->latestRevisionData["$id"]['revId'];
	}

	public function getLatestRevisionTimestamp( PropertyId $id ): string {
		return $this->latestRevisionData["$id"]['revTime'];
	}

	public function getLatestRevisionEditMetadata( PropertyId $id ): EditMetadata {
		return $this->latestRevisionData["$id"]['editMetadata'];
	}

	public function getProperty( PropertyId $propertyId ): ?Property {
		return $this->properties[$propertyId->getSerialization()] ?? null;
	}

	public function update( Property $property, EditMetadata $editMetadata ): PropertyRevision {
		$this->properties[$property->getId()->getSerialization()] = $property;
		$revisionData = [
			'revId' => rand(),
			// using the real date/time here is a bit dangerous, but should be ok as long as revId is also checked.
			'revTime' => date( 'YmdHis' ),
			'editMetadata' => $editMetadata,
		];
		$this->latestRevisionData[$property->getId()->getSerialization()] = $revisionData;

		return new PropertyRevision(
			new ReadModelProperty(
				Labels::fromTermList( $property->getLabels() ),
				Descriptions::fromTermList( $property->getDescriptions() ),
				Aliases::fromAliasGroupList( $property->getAliasGroups() ),
				new StatementList() // TODO in a later patch
			),
			$revisionData['revTime'],
			$revisionData['revId']
		);
	}
}
