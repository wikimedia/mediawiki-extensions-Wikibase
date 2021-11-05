<?php

declare( strict_types=1 );

namespace Wikibase\Lib;

use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ServiceByTypeDispatcher {

	/** @var callable[] */
	private $callbacks;

	/**
	 * Map of entity types to services that were created by the respective callback
	 * @var object[]
	 */
	private $services;

	/** @var object */
	private $defaultService;

	/** @var string */
	private $type;

	/**
	 * @param string $type type of the dispatched services, i.e. type of the default service and the return value of the callbacks
	 * @param callable[] $callbacks map of entity types to callbacks creating the service to be used
	 * @param object $defaultService the service to be used when there is no callback defined for the given entity type
	 */
	public function __construct( string $type, array $callbacks, object $defaultService ) {
		Assert::parameterElementType( 'callable', $callbacks, '$callbacks' );
		Assert::parameterKeyType( 'string', $callbacks, '$callbacks' );
		Assert::parameterType( $type, $defaultService, '$defaultService' );

		$this->callbacks = $callbacks;
		$this->defaultService = $defaultService;
		$this->type = $type;
	}

	/**
	 * @param string $entityType
	 * @param array $callbackArgs arguments to be passed to the service creation callback
	 *
	 * @return object
	 */
	public function getServiceForType( string $entityType, array $callbackArgs = [] ) {
		if ( !array_key_exists( $entityType, $this->callbacks ) ) {
			return $this->defaultService;
		}

		return $this->services[$entityType] ?? $this->createService( $entityType, $callbackArgs );
	}

	private function createService( string $entityType, array $args = [] ) {
		$this->services[$entityType] = $this->callbacks[$entityType]( ...$args );

		Assert::postcondition(
			$this->services[$entityType] instanceof $this->type,
			"callback must return an instance of $this->type"
		);

		return $this->services[$entityType];
	}

}
