<?php

declare( strict_types=1 );

namespace Wikibase\DataAccess;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupFactory {
	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;
	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;
	/**
	 * @var SingleEntitySourceServicesFactory
	 */
	private $singleEntitySourceServicesFactory;
	/**
	 * @var array
	 */
	private $lookupBySource;

	/**
	 * @param EntitySourceDefinitions $entitySourceDefinitions
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 * @param SingleEntitySourceServicesFactory $singleEntitySourceServicesFactory
	 */
	public function __construct(
		EntitySourceDefinitions $entitySourceDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		SingleEntitySourceServicesFactory $singleEntitySourceServicesFactory
	) {
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->entityTypeDefinitions = $entityTypeDefinitions;
		$this->singleEntitySourceServicesFactory = $singleEntitySourceServicesFactory;

		$this->lookupBySource = [];
	}

	public function getLookupForType( string $type ): PrefetchingTermLookup {
		$prefetchingTermLookupCallbacks = $this->entityTypeDefinitions
			->get( EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK );
		$entitySource = $this->entitySourceDefinitions->getSourceForEntityType( $type );

		Assert::parameter(
			array_key_exists( $type, $prefetchingTermLookupCallbacks ),
			"type",
			"Entity type definition for $type must provide a callback to generate a PrefetchingTermLookup"
		);

		Assert::precondition(
			$entitySource !== null,
			"Entity type $type must be defined for a specific entity source"
		);

		$callback = $prefetchingTermLookupCallbacks[ $type ];
		$sourceServices = $this->singleEntitySourceServicesFactory->getServicesForSource( $entitySource );
		$lookup = call_user_func( $callback, $sourceServices );

		Assert::postcondition(
			$lookup instanceof PrefetchingTermLookup,
			"Callback creating a lookup for $type must create an instance of PrefetchingTermLookup"
		);

		return $lookup;
	}

	public function getLookupForSource( EntitySource $source ): PrefetchingTermLookup {
		$sourceName = $source->getSourceName();
		if ( !array_key_exists( $sourceName, $this->lookupBySource ) ) {
			$this->lookupBySource[ $sourceName ] = $this->newLookupForSource( $source );
		}

		return $this->lookupBySource[ $sourceName ];
	}

	private function newLookupForSource( EntitySource $source ): PrefetchingTermLookup {
		$entitySources = $this->entitySourceDefinitions->getSources();

		$prefetchingTermLookupCallbacks = $this->entityTypeDefinitions
			->get( EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK );

		Assert::parameter(
			in_array( $source, $entitySources ),
			"source",
			"Entity source must be defined"
		);

		$typesWithCustomLookups = array_keys( $prefetchingTermLookupCallbacks );
		$lookupConstructorsByType = array_intersect( $typesWithCustomLookups, $source->getEntityTypes() );
		$customLookups = array_combine(
			$lookupConstructorsByType,
			array_map(
				[ $this, 'getLookupForType' ],
				$lookupConstructorsByType
			)
		);

		return new ByTypeDispatchingPrefetchingTermLookup(
			$customLookups,
			new NullPrefetchingTermLookup()
		);
	}
}
