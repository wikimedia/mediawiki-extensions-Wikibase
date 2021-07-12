<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use InvalidArgumentException;
use Wikibase\DataAccess\EntitySource;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class EntitySourceAndTypeDefinitions {

	/**
	 * @var EntityTypeDefinitions[]
	 */
	private $entityTypeDefinitionsBySourceType;

	/**
	 * @var EntitySource[]
	 */
	private $entitySources;

	/**
	 * @param EntityTypeDefinitions[] $entityTypeDefinitionsBySourceType maps entity source type to EntityTypeDefinitions
	 * @param EntitySource[] $entitySources
	 */
	public function __construct(
		array $entityTypeDefinitionsBySourceType,
		array $entitySources
	) {
		Assert::parameterElementType(
			EntityTypeDefinitions::class,
			$entityTypeDefinitionsBySourceType,
			'$entityTypeDefinitionsBySourceType'
		);
		Assert::parameterElementType(
			EntitySource::class,
			$entitySources,
			'$entitySources'
		);
		$this->assertNoUnknownEntitySourceTypes( $entityTypeDefinitionsBySourceType, $entitySources );

		$this->entitySources = $entitySources;
		$this->entityTypeDefinitionsBySourceType = $entityTypeDefinitionsBySourceType;
	}

	public function getServiceBySourceAndType( string $serviceName ): array {
		$services = [];

		foreach ( $this->entitySources as $source ) {
			$services[$source->getSourceName()] = $this->entityTypeDefinitionsBySourceType[$source->getType()]->get( $serviceName );
		}

		return $services;
	}

	private function assertNoUnknownEntitySourceTypes( array $entityTypeDefinitionsBySourceType, array $entitySources ) {
		foreach ( $entitySources as $source ) {
			if ( !array_key_exists( $source->getType(), $entityTypeDefinitionsBySourceType ) ) {
				throw new InvalidArgumentException( 'unknown type of entity source: "' . $source->getType() . '"' );
			}
		}
	}

}
