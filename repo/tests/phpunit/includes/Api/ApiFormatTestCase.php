<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use FauxRequest;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Addshore
 */
abstract class ApiFormatTestCase extends MediaWikiIntegrationTestCase {

	/**
	 * @var PropertyId|null
	 */
	protected $lastPropertyId;

	/**
	 * @var PropertyId|null
	 */
	protected $lastItemId;

	/** @var User */
	protected $user;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->user = $this->getTestUser()->getUser();
	}

	/**
	 * @param string $moduleClass
	 * @param string $moduleName
	 * @param array $params
	 * @param bool $needsToken
	 *
	 * @return ApiMain
	 */
	protected function getApiModule( $moduleClass, $moduleName, array $params, $needsToken = false ) {
		global $wgAPIModules;

		if ( $needsToken ) {
			$params['token'] = $this->user->getEditToken();
		}
		$request = new FauxRequest( $params, true );
		$ctx = new \ApiTestContext();
		$ctx = $ctx->newTestContext( $request, $this->user );
		$main = new ApiMain( $ctx, true );

		return ObjectFactory::getObjectFromSpec( $wgAPIModules[$moduleName], [
			'assertClass' => $moduleClass,
			'extraArgs' => [ $main, $moduleName ],
			'serviceContainer' => MediaWikiServices::getInstance(),
		] );
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
		$store = WikibaseRepo::getEntityStore();

		$property = Property::newFromType( 'string' );
		$entityRevision = $store->saveEntity( $property, 'testing', $this->user, EDIT_NEW );
		$this->lastPropertyId = $entityRevision->getEntity()->getId();
	}

	protected function storeNewItem() {
		$store = WikibaseRepo::getEntityStore();

		$item = new Item();
		$entityRevision = $store->saveEntity( $item, 'testing', $this->user, EDIT_NEW );
		$this->lastItemId = $entityRevision->getEntity()->getId();

		return $entityRevision;
	}

	private function storePresetDataInStatement( EntityRevision $entityRevision, PropertyId $propertyId ) {
		$store = WikibaseRepo::getEntityStore();

		/** @var Item $item */
		$item = $entityRevision->getEntity();
		$snak = new PropertyNoValueSnak( $propertyId );
		$guid = $item->getId()->getSerialization() . '$1111AAAA-43cb-ed6d-3adb-760e85bd17ee';
		$item->getStatements()->addNewStatement( $snak, null, null, $guid );

		$item->setLabel( 'en', 'en-label' );
		$item->setLabel( 'de', 'de-label' );
		$item->setDescription( 'de', 'de-desc' );
		$item->setDescription( 'es', 'es-desc' );
		$item->setAliases( 'pt', [ 'AA', 'BB' ] );
		$item->setAliases( 'en', [ 'AA-en', 'BB-en' ] );

		$entityRevision = $store->saveEntity( $item, 'testing more!', $this->user );

		return $entityRevision;
	}

}
