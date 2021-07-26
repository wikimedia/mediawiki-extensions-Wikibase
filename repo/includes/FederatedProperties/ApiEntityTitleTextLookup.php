<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ApiEntityTitleTextLookup implements EntityTitleTextLookup {

	/**
	 * @var ApiEntityNamespaceInfoLookup
	 */
	private $namespaceLookup;

	public function __construct( ApiEntityNamespaceInfoLookup $namespaceLookup ) {
		$this->namespaceLookup = $namespaceLookup;
	}

	public function getPrefixedText( EntityId $id ): ?string {
		Assert::parameterType( FederatedPropertyId::class, $id, '$id' );
		/** @var FederatedPropertyId $id */
		'@phan-var FederatedPropertyId $id';

		$namespaceName = $this->namespaceLookup->getNamespaceNameForEntityType( $id->getEntityType() );

		if ( $namespaceName === null ) {
			return null;
		}

		return $this->getTitleString( $namespaceName, $id );
	}

	private function getTitleString( $namespaceName, FederatedPropertyId $id ) {
		if ( $namespaceName === '' ) {
			return $id->getSerialization();
		}
		return $namespaceName . ':' . $id->getRemoteIdSerialization();
	}

}
