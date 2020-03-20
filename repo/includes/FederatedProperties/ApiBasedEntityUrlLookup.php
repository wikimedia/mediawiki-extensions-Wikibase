<?php

namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityUrlLookup;

/**
 * @license GPL-2.0-or-later
 */
class ApiBasedEntityUrlLookup implements EntityUrlLookup {

	/**
	 * @var ApiBasedEntityNamespaceInfoLookup
	 */
	private $namespaceLookup;

	/**
	 * @var string
	 */
	private $sourceWikibaseUrl;

	public function __construct( ApiBasedEntityNamespaceInfoLookup $namespaceLookup, string $sourceWikibaseUrl ) {
		$this->namespaceLookup = $namespaceLookup;
		$this->sourceWikibaseUrl = $sourceWikibaseUrl;
	}

	public function getFullUrl( EntityId $id ): ?string {
		$namespaceName = $this->namespaceLookup->getNamespaceNameForEntityType( $id->getEntityType() );

		if ( $namespaceName === null ) {
			return null;
		}

		return $this->sourceWikibaseUrl . 'index.php?' . http_build_query( [
				'title' => $this->getTitleString( $namespaceName, $id ),
			] );
	}

	private function getTitleString( $namespaceName, EntityId $id ) {
		if ( $namespaceName === '' ) {
			return $id->getSerialization();
		}
		return $namespaceName . ':' . $id->getSerialization();
	}

}
