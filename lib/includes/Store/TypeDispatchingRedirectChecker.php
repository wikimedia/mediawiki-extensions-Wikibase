<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingRedirectChecker implements EntityRedirectChecker {

	/**
	 * @var array
	 */
	private $callbacks;

	/**
	 * @var EntityRedirectChecker
	 */
	private $defaultRedirectChecker;

	/**
	 * @var EntityRedirectChecker[]
	 */
	private $redirectCheckers;

	public function __construct( array $callbacks, EntityRedirectChecker $defaultRedirectChecker ) {
		$this->callbacks = $callbacks;
		$this->defaultRedirectChecker = $defaultRedirectChecker;
	}

	public function isRedirect( EntityId $id ): bool {
		return $this->getRedirectCheckerForType( $id )->isRedirect( $id );
	}

	private function getRedirectCheckerForType( EntityId $id ): EntityRedirectChecker {
		$entityType = $id->getEntityType();
		if ( !array_key_exists( $entityType, $this->callbacks ) ) {
			return $this->defaultRedirectChecker;
		}

		return $this->redirectCheckers[$entityType] ?? $this->createRedirectChecker( $entityType );
	}

	private function createRedirectChecker( string $entityType ): EntityRedirectChecker {
		$this->redirectCheckers[$entityType] = $this->callbacks[$entityType]();

		return $this->redirectCheckers[$entityType];
	}

}
