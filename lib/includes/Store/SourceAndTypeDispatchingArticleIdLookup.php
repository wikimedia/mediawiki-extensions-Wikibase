<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;

/**
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingArticleIdLookup implements EntityArticleIdLookup {

	/**
	 * @var ServiceBySourceAndTypeDispatcher
	 */
	private $serviceDispatcher;
	/**
	 * @var EntitySourceLookup
	 */
	private $lookup;

	public function __construct( EntitySourceLookup $lookup, ServiceBySourceAndTypeDispatcher $dispatcher ) {
		$this->serviceDispatcher = $dispatcher;
		$this->lookup = $lookup;
	}

	public function getArticleId( EntityId $id ): ?int {
		$sourceName = $this->lookup->getEntitySourceById( $id )->getSourceName();
		return $this->serviceDispatcher->getServiceForSourceAndType( $sourceName, $id->getEntityType() )
			->getArticleId( $id );
	}

}
