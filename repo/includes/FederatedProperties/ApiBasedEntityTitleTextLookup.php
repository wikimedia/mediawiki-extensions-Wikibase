<?php

namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleTextLookup;

/**
 * @license GPL-2.0-or-later
 */
class ApiBasedEntityTitleTextLookup implements EntityTitleTextLookup {

	/**
	 * @var ApiBasedEntityNamespaceInfoLookup
	 */
	private $namespaceLookup;

	public function __construct( ApiBasedEntityNamespaceInfoLookup $namespaceLookup ) {
		$this->namespaceLookup = $namespaceLookup;
	}

	public function getPrefixedText( EntityId $id ): ?string {
		$namespaceName = $this->namespaceLookup->getNamespaceNameForEntityType( $id->getEntityType() );

		if ( $namespaceName === null ) {
			return null;
		}

		return $this->getTitleString( $namespaceName, $id );
	}

	private function getTitleString( $namespaceName, EntityId $id ) {
		if ( $namespaceName === '' ) {
			return $id->getSerialization();
		}
		return $namespaceName . ':' . $id->getSerialization();
	}

}
