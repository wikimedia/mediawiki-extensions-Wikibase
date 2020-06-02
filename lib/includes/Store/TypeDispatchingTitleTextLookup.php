<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceByTypeDispatcher;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingTitleTextLookup implements EntityTitleTextLookup {

	/**
	 * @var ServiceByTypeDispatcher
	 */
	private $serviceDispatcher;

	public function __construct( array $callbacks, EntityTitleTextLookup $defaultLookup ) {
		$this->serviceDispatcher = new ServiceByTypeDispatcher( EntityTitleTextLookup::class, $callbacks, $defaultLookup );
	}

	public function getPrefixedText( EntityId $id ): ?string {
		return $this->serviceDispatcher->getServiceForType( $id->getEntityType() )
			->getPrefixedText( $id );
	}

}
