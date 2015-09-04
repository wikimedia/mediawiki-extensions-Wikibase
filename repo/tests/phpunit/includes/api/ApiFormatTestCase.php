<?php

namespace Wikibase\Test\Repo\Api;

use ApiBase;
use ApiMain;
use FauxRequest;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adam Shorland
 */
abstract class ApiFormatTestCase extends \MediaWikiTestCase {

	/**
	 * @var PropertyId|null
	 */
	protected $lastPropertyId;

	/**
	 * @var PropertyId|null
	 */
	protected $lastItemId;

	/**
	 * @param string $moduleClass
	 * @param string $moduleName
	 * @param array $params
	 * @param bool $needsToken
	 *
	 * @return ApiMain
	 */
	protected function getApiModule( $moduleClass, $moduleName, array $params, $needsToken = false ) {
		global $wgUser;

		if ( $needsToken ) {
			$params['token'] = $wgUser->getEditToken();
		}
		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );

		return new $moduleClass( $main, $moduleName );
	}

	protected function getNewEntityRevision( $withData = false ) {
		$entityRevision = $this->storeNewItem();

		if ( $withData ) {
			$this->storeNewProperty();
			$entityRevision = $this->storePresetDataInStatement( $entityRevision, $this->lastPropertyId );
		}

		return $entityRevision;
	}

	protected function storeNewProperty() {
		global $wgUser;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$property = Property::newFromType( 'string' );
		$entityRevision = $store->saveEntity( $property, 'testing', $wgUser, EDIT_NEW );
		$this->lastPropertyId = $entityRevision->getEntity()->getId();
	}

	protected function storeNewItem() {
		global $wgUser;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = new Item();
		$entityRevision = $store->saveEntity( $item, 'testing', $wgUser, EDIT_NEW );
		$this->lastItemId = $entityRevision->getEntity()->getId();

		return $entityRevision;
	}

}
