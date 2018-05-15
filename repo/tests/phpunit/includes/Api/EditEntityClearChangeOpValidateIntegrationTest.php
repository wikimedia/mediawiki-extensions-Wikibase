<?php

namespace Wikibase\Repo\Tests\Api;

use PHPUnit4And6Compat;
use ReflectionClass;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\EditEntity
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EditEntityClearChangeOpValidateIntegrationTest extends \MediaWikiTestCase {

	use PHPUnit4And6Compat;

	public function testGivenNotClearedEntity_validateReturnsSuccess() {
		$item = $this->newItem();

		$changeOp = $this->newChangeOp();

		$changeOpResult = $changeOp->validate( $item );

		$this->assertTrue( $changeOpResult->isValid() );
	}

	public function testGivenClearedEntity_validateReturnsError() {
		$item = $this->newItem();
		$item->clear();

		$changeOp = $this->newChangeOp();

		$changeOpResult = $changeOp->validate( $item );

		$this->assertFalse( $changeOpResult->isValid() );
	}

	public function testGivenNotClearedEntity_applyPasses() {
		$item = $this->newItem();

		$changeOp = $this->newChangeOp();

		$changeOp->apply( $item );

		$this->assertTrue( true );
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testGivenClearedEntity_applyExplodes() {
		$item = $this->newItem();
		$item->clear();

		$changeOp = $this->newChangeOp();

		$changeOp->apply( $item );
	}

	public function testGivenNotClearedEntity_apiIsOk() {
		$item = $this->newItem();

		$this->saveItem( $item );

		$changeOp = $this->newChangeOp();

		$params = $this->getParamsNoClear();

		$api = $this->newApi( $params );

		$modifyEntity = ( new ReflectionClass( $api ) )
			->getMethod( 'modifyEntity' );
		$modifyEntity->setAccessible( true );

		$modifyEntity->invokeArgs( $api, [ &$item, $changeOp, $params ] );

		$this->assertTrue( true );
	}

		/**
	 * @expectedException \ApiUsageException
	 */
	public function testGivenNotClearedEntityAndClearParameter_apiErrors() {
		$item = $this->newItem();

		$this->saveItem( $item );

		$changeOp = $this->newChangeOp();

		$params = $this->getParamsWithClear();

		$api = $this->newApi( $params );

		$modifyEntity = ( new ReflectionClass( $api ) )
			->getMethod( 'modifyEntity' );
		$modifyEntity->setAccessible( true );

		$modifyEntity->invokeArgs( $api, [ &$item, $changeOp, $params ] );
	}

	private function newApi( array $params ) {
		$request = new \FauxRequest( $params );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
		return new EditEntity(
			new \ApiMain( $request ),
			'test',
			$wikibaseRepo->getTermsLanguages(),
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getEntityFactory(),
			$wikibaseRepo->getExternalFormatStatementDeserializer(),
			$wikibaseRepo->getDataTypeDefinitions()->getTypeIds(),
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
			$wikibaseRepo->getEntityChangeOpProvider()
		);
	}

	/**
	 * @return ChangeOp
	 */
	private function newChangeOp() {
		$changeOp = $this->getMock( ChangeOp::class );

		$changeOp->method( 'validate' )
			->willReturnCallback( function( Item $item ) {
				if ( $item->getLabels()->isEmpty() ) {
					return Result::newError( [ Error::newError( 'item without labels is no good' ) ] );
				}
				return Result::newSuccess();
			} );

		$changeOp->method( 'apply' )
			->willReturnCallback( function( Item $item ) {
				if ( $item->getLabels()->isEmpty() ) {
					throw new \RuntimeException( 'item without labels is really no good' );
				}
			} );

		return $changeOp;
	}

	/**
	 * @return Item
	 */
	private function newItem() {
		return new Item(
			new ItemId( 'Q666' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'test item' ) ] ) )
		);
	}

	private function saveItem( Item $item ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, __METHOD__, $this->getTestUser()->getUser() );
	}

	/**
	 * @return array
	 */
	private function getParamsNoClear() {
		return [ 'data' => '', 'clear' => false, 'summary' => null ];
	}

	/**
	 * @return array
	 */
	private function getParamsWithClear() {
		return [ 'data' => '', 'clear' => true, 'summary' => null ];
	}

}
