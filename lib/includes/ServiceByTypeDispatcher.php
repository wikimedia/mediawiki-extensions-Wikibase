<?php

declare( strict_types=1 );

namespace Wikibase\Lib;

use Wikimedia\Assert\Assert;

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
	 * @param callable[] $callbacks map of entity types to callbacks creating the service to be used
	 * @param object $defaultService - the service to be used when there is no callback defined for the given entity type
	 */
	public function __construct( array $callbacks, object $defaultService ) {
		Assert::parameterElementType( 'callable', $callbacks, '$callbacks' );
		Assert::parameterKeyType( 'string', $callbacks, '$callbacks' );
		Assert::parameterType( 'object', $defaultService, '$defaultService' );
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
