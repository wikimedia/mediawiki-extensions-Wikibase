<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use LogicException;
use Wikibase\DataAccess\EntitySource;

/**
 * @license GPL-2.0-or-later
 */
class EntitySourceAndTypeDefinitions {

	/**
	 * @var EntityTypeDefinitions
	 */
	private $databaseEntityTypeDefinitions;

	/**
	 * @var EntityTypeDefinitions
	 */
	private $apiEntityTypeDefinitions;

	/**
	 * @var EntitySource[]
	 */
	private $entitySources;

	public function __construct(
		EntityTypeDefinitions $databaseEntityTypeDefinitions,
		EntityTypeDefinitions $apiEntityTypeDefinitions,
		array $entitySources
	) {
		$this->databaseEntityTypeDefinitions = $databaseEntityTypeDefinitions;
		$this->apiEntityTypeDefinitions = $apiEntityTypeDefinitions;
		$this->entitySources = $entitySources;
	}

	public function getServiceBySourceAndType( string $serviceName ): array {
		$services = [];

		foreach ( $this->entitySources as $source ) {
			if ( $source->getType() === EntitySource::TYPE_DB ) {
				$services[$source->getSourceName()] = $this->databaseEntityTypeDefinitions->get( $serviceName );
			} elseif ( $source->getType() === EntitySource::TYPE_API ) {
				$services[$source->getSourceName()] = $this->apiEntityTypeDefinitions->get( $serviceName );
			} else {
				throw new LogicException( 'unknown type of entity source: "' . $source->getType() . '"' );
			}
		}

		return $services;
	}

}
