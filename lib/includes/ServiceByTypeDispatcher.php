<?php

declare( strict_types=1 );

namespace Wikibase\Lib;

/**
 * @license GPL-2.0-or-later
 */
class ServiceByTypeDispatcher {

	private $callbacks;

	/**
	 * Map of entity types to services that were created by the respective callback
	 */
	private $services;

	private $defaultService;

	/**
	 * @param $callbacks callable[] map of entity types to callbacks creating the service to be used
	 * @param $defaultService - the service to be used when there is no callback defined for the given entity type
	 */
	public function __construct( array $callbacks, $defaultService ) {
		$this->callbacks = $callbacks;
		$this->defaultService = $defaultService;
	}

	public function getServiceForType( string $entityType ) {
		if ( !array_key_exists( $entityType, $this->callbacks ) ) {
			return $this->defaultService;
		}

		return $this->services[$entityType] ?? $this->createService( $entityType );
	}

	private function createService( string $entityType ) {
		$this->services[$entityType] = $this->callbacks[$entityType]();

		return $this->services[$entityType];
	}

}
