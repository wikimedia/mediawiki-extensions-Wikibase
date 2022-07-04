<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use MediaWikiIntegrationTestCase;
use ReflectionClass;
use RuntimeException;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\Api\EditSummaryHelper;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCollector;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpApplyException;
use Wikibase\Repo\ChangeOp\NonLanguageBoundChangesCounter;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpResultStub;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\EditEntity
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EditEntityClearChangeOpValidateIntegrationTest extends MediaWikiIntegrationTestCase {

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

	public function testGivenClearedEntity_applyExplodes() {
		$item = $this->newItem();
		$item->clear();

		$changeOp = $this->newChangeOp();

		$this->expectException( RuntimeException::class );
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

	public function testGivenNotClearedEntityAndClearParameter_apiErrors() {
		$item = $this->newItem();

		$this->saveItem( $item );

		$changeOp = $this->newChangeOp();

		$params = $this->getParamsWithClear();

		$api = $this->newApi( $params );

		$modifyEntity = ( new ReflectionClass( $api ) )
			->getMethod( 'modifyEntity' );
		$modifyEntity->setAccessible( true );

		$this->expectException( ApiUsageException::class );
		$modifyEntity->invokeArgs( $api, [ &$item, $changeOp, $params ] );
	}

	private function newApi( array $params ) {
		$request = new \FauxRequest( $params );

		return new EditEntity(
			new \ApiMain( $request ),
			'test',
			new \NullStatsdDataFactory(),
			WikibaseRepo::getStore()
				->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getDataTypeDefinitions()->getTypeIds(),
			WikibaseRepo::getEntityChangeOpProvider(),
			new EditSummaryHelper(
				new ChangedLanguagesCollector(),
				new ChangedLanguagesCounter(),
				new NonLanguageBoundChangesCounter()
			),
			false,
			[ 'mainItem' => 'Q42' ]
		);
	}

	/**
	 * @return ChangeOp
	 */
	private function newChangeOp() {
		$changeOp = $this->createMock( ChangeOp::class );

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
					throw new ChangeOpApplyException( 'item without labels is really no good' );
				}

				return new ChangeOpResultStub( $item->getId(), true );
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
		$store = WikibaseRepo::getEntityStore();
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
