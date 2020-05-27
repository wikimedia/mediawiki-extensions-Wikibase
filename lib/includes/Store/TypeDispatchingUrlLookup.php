<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceByTypeDispatcher;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingUrlLookup implements EntityUrlLookup {

	/**
	 * @var ServiceByTypeDispatcher
	 */
	private $serviceDispatcher;

	public function __construct( array $callbacks, EntityUrlLookup $defaultLookup ) {
		$this->serviceDispatcher = new ServiceByTypeDispatcher( EntityUrlLookup::class, $callbacks, $defaultLookup );
	}

	public function getFullUrl( EntityId $id ): ?string {
		return $this->serviceDispatcher->getServiceForType( $id->getEntityType() )
			->getFullUrl( $id );
	}

	public function getLinkUrl( EntityId $id ): ?string {
		return $this->serviceDispatcher->getServiceForType( $id->getEntityType() )
			->getLinkUrl( $id );
	}

}
