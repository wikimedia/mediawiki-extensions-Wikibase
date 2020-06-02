<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceByTypeDispatcher;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingRedirectChecker implements EntityRedirectChecker {

	/**
	 * @var ServiceByTypeDispatcher
	 */
	private $serviceDispatcher;

	public function __construct( array $callbacks, EntityRedirectChecker $defaultRedirectChecker ) {
		$this->serviceDispatcher = new ServiceByTypeDispatcher( EntityRedirectChecker::class, $callbacks, $defaultRedirectChecker );
	}

	public function isRedirect( EntityId $id ): bool {
		return $this->serviceDispatcher->getServiceForType( $id->getEntityType() )
			->isRedirect( $id );
	}

}
