<?php

declare( strict_types=1 );

namespace Wikibase\Lib;

use LogicException;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ServiceBySourceAndTypeDispatcher {

	/** @var array[] */
	private $callbacks;

	/**
	 * Map of entity source names and types to services that were created by the respective callback
	 * @var array[]
	 */
	private $services;

	/** @var string */
	private $serviceType;

	/**
	 * @param string $type type of the dispatched services, i.e. type of the return value of the callbacks
	 * @param array[][] $callbacks map of entity source names and types to callbacks creating the service to be used
	 */
	public function __construct( string $type, array $callbacks ) {
		Assert::parameterElementType( 'array', $callbacks, '$callbacks' );
		//TODO: we don't check that the array is a 2D array of callables only keyed by string.
		Assert::parameterKeyType( 'string', $callbacks, '$callbacks' );
		if ( count( $callbacks ) === 0 ) {
			throw new LogicException( 'Callback array cannot be empty' );
		}

		$this->callbacks = $callbacks;
		$this->serviceType = $type;
	}

	private function createService( string $sourceName, string $entityType, array $args = [] ) {
		if ( isset( $this->callbacks[$sourceName][$entityType] ) ) {
			$this->services[$sourceName][$entityType] = $this->callbacks[$sourceName][$entityType]( ...$args );

			Assert::postcondition(
				$this->services[$sourceName][$entityType] instanceof $this->serviceType,
				"callback must return an instance of $this->serviceType"
			);

			return $this->services[$sourceName][$entityType];
		}
		throw new LogicException(
			'Unable to find ' . $this->serviceType . ' Service callback for Entity Type ' . $entityType . ' for Source ' . $sourceName
		);
	}

	public function getServiceForSourceAndType( string $sourceName, string $entityType, array $callbackArgs = [] ) {
		return $this->services[$sourceName][$entityType] ?? $this->createService( $sourceName, $entityType, $callbackArgs );
	}

}
