<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceByTypeDispatcher;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingArticleIdLookup implements EntityArticleIdLookup {

	/**
	 * @var ServiceByTypeDispatcher
	 */
	private $serviceDispatcher;

	public function __construct( array $callbacks, EntityArticleIdLookup $defaultLookup ) {
		$this->serviceDispatcher = new ServiceByTypeDispatcher( EntityArticleIdLookup::class, $callbacks, $defaultLookup );
	}

	public function getArticleId( EntityId $id ): ?int {
		return $this->serviceDispatcher->getServiceForType( $id->getEntityType() )
			->getArticleId( $id );
	}

}
