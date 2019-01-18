<?php

namespace Wikibase\DataAccess;

/**
 * @license GPL-2.0-or-later
 */
class MultipleEntitySourceServices {

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @var SingleEntitySourceServices[]
	 */
	private $singleSourceServices;

	public function __construct( EntitySourceDefinitions $entitySourceDefinitions, array $singleSourceServices ) {
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->singleSourceServices = $singleSourceServices;
	}

	public function getEntityRevisionLookup() {
		$lookupsPerType = [];

		/** @var EntitySource $source */
		foreach ( $this->entitySourceDefinitions->getEntityTypeToSourceMapping() as $entityType => $source ) {
			$lookupsPerType[$entityType] = $this->singleSourceServices[$source->getSourceName()]->getEntityRevisionLookup();
		}

		return new ByTypeDispatchingEntityRevisionLookup( $lookupsPerType );
		// TODO: only create single instance
	}

}
