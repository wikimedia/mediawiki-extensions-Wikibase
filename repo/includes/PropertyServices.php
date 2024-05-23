<?php

namespace Wikibase\Repo;

use LogicException;
use MediaWiki\MediaWikiServices;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\Lib\PropertyInfoDataTypeLookup;

/**
 * @license GPL-2.0-or-later
 */
class PropertyServices {

	public const PROPERTY_DATA_TYPE_LOOKUP_CALLBACK = 'property-data-type-lookup-callback';

	/**
	 * @var EntitySourceDefinitions
	 */
	private $sourceDefinitions;

	/**
	 * @var array
	 */
	private $serviceDefinitions;

	/**
	 * @param EntitySourceDefinitions $sourceDefinitions
	 * @param callable[] $serviceDefinitions keyed by source type
	 */
	public function __construct( EntitySourceDefinitions $sourceDefinitions, array $serviceDefinitions ) {
		$this->sourceDefinitions = $sourceDefinitions;
		$this->serviceDefinitions = $serviceDefinitions;
	}

	/**
	 * @param string $serviceName
	 *
	 * @return callable[] keyed by source name
	 */
	public function get( string $serviceName ): array {
		if ( !array_key_exists( $serviceName, $this->serviceDefinitions ) ) {
			throw new LogicException( "Undefined service '$serviceName'" );
		}

		$services = [];

		foreach ( $this->sourceDefinitions->getSources() as $source ) {
			$services[$source->getSourceName()] = $this->serviceDefinitions[$serviceName][$source->getType()];
		}

		return $services;
	}

	public static function getServiceDefinitions( MediaWikiServices $services ): array {
		return [
			self::PROPERTY_DATA_TYPE_LOOKUP_CALLBACK => [
				ApiEntitySource::TYPE => function () use ( $services ) {
					return WikibaseRepo::getFederatedPropertiesServiceFactory( $services )
						->newApiPropertyDataTypeLookup();
				},
				DatabaseEntitySource::TYPE => function () use ( $services ) {
					$infoLookup = WikibaseRepo::getPropertyInfoLookup( $services );
					$entityLookup = WikibaseRepo::getEntityLookup( $services );
					$retrievingLookup = new EntityRetrievingDataTypeLookup( $entityLookup );

					return new PropertyInfoDataTypeLookup(
						$infoLookup,
						WikibaseRepo::getLogger( $services ),
						$retrievingLookup
					);
				},
			],
		];
	}

}
