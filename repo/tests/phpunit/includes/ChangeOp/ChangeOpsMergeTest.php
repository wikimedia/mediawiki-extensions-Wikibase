<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use HashSiteStore;
use InvalidArgumentException;
use MediaWikiTestCase;
use Site;
use TestSites;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Tests\ItemBuilder;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\EntityValidator;

/**
 * @covers Wikibase\ChangeOp\ChangeOpsMerge
 *
 * @group Wikibase
 * @group ChangeOp
 * @group Database
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class ChangeOpsMergeTest extends MediaWikiTestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	private $mockProvider;

	/**
	 * @param string|null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
	}

	protected function makeChangeOpsMerge(
		Item $fromItem,
		Item $toItem,
		array $ignoreConflicts = array(),
		$siteLookup = null
	) {
		if ( $siteLookup === null ) {
			$siteLookup = new HashSiteStore( TestSites::getSites() );
		}
		// A validator which makes sure that no site link is for page 'DUPE'
		$siteLinkUniquenessValidator = $this->getMock( EntityValidator::class );
		$siteLinkUniquenessValidator->expects( $this->any() )
			->method( 'validateEntity' )
			->will( $this->returnCallback( function( Item $item ) {
				foreach ( $item->getSiteLinkList()->toArray() as $siteLink ) {
					if ( $siteLink->getPageName() === 'DUPE' ) {
						return Result::newError( array( Error::newError( 'SiteLink conflict' ) ) );
					}
				}
				return Result::newSuccess();
			} ) );

		$constraintProvider = $this->getMockBuilder( EntityConstraintProvider::class )
			->disableOriginalConstructor()
			->getMock();
		$constraintProvider->expects( $this->any() )
			->method( 'getUpdateValidators' )
			->will( $this->returnValue( array( $siteLinkUniquenessValidator ) ) );

		$changeOpFactoryProvider = new ChangeOpFactoryProvider(
			$constraintProvider,
			new GuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $toItem->getId() ),
			$this->mockProvider->getMockSnakValidator(),
			$this->mockProvider->getMockTermValidatorFactory(),
			$siteLookup,
			[]
		);

		return new ChangeOpsMerge(
			$fromItem,
			$toItem,
			$ignoreConflicts,
			$constraintProvider,
			$changeOpFactoryProvider,
			$siteLookup
		);
	}

	/**
	 * @dataProvider provideValidConstruction
	 */
	public function testCanConstruct( Item $from, Item $to, array $ignoreConflicts ) {
		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			$ignoreConflicts
		);
		$this->assertInstanceOf( ChangeOpsMerge::class, $changeOps );
	}

	public function provideValidConstruction() {
		$from = $this->newItemWithId( 'Q111' );
		$to = $this->newItemWithId( 'Q222' );
		return array(
			array( $from, $to, array() ),
			array( $from, $to, array( 'sitelink' ) ),
			array( $from, $to, array( 'statement' ) ),
			array( $from, $to, array( 'description' ) ),
			array( $from, $to, array( 'description', 'sitelink' ) ),
		);
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidIgnoreConflicts( Item $from, Item $to, array $ignoreConflicts ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->makeChangeOpsMerge(
			$from,
			$to,
			$ignoreConflicts
		);
	}

	public function provideInvalidConstruction() {
		$from = $this->newItemWithId( 'Q111' );
		$to = $this->newItemWithId( 'Q222' );
		return array(
			array( $from, $to, array( 'label' ) ),
			array( $from, $to, array( 'foo' ) ),
			array( $from, $to, array( 'description', 'foo' ) ),
		);
	}

	private function newItemWithId( $idString ) {
		return ItemBuilder::create()->withId( $idString )->build();
	}

	/**
	 * @dataProvider provideData
	 */
	public function testCanApply(
		Item $from,
		Item $to,
		Item $expectedFrom,
		Item $expectedTo,
		array $ignoreConflicts = array()
	) {
		$from->setId( new ItemId( 'Q111' ) );
		$to->setId( new ItemId( 'Q222' ) );

		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			$ignoreConflicts
		);

		$changeOps->apply();

		$this->removeClaimsGuids( $from );
		$this->removeClaimsGuids( $expectedFrom );
		$this->removeClaimsGuids( $to );
		$this->removeClaimsGuids( $expectedTo );

		$this->assertTrue( $from->equals( $expectedFrom ) );
		$this->assertTrue( $to->equals( $expectedTo ) );
	}

	private function removeClaimsGuids( Item $item ) {
		/** @var Statement $statement */
		foreach ( $item->getStatements() as $statement ) {
			$statement->setGuid( null );
		}
	}

	/**
	 * @return array 1=>from 2=>to 3=>expectedFrom 4=>expectedTo
	 */
	public function provideData() {
		$testCases = array();
		$emptyItem = ItemBuilder::create()->build();

		$itemWithEnLabel = ItemBuilder::create()
			->withLabel( 'en', 'foo' )
			->build();

		$testCases['labelMerge'] = array(
			$itemWithEnLabel->copy(),
			$emptyItem->copy(),
			$emptyItem->copy(),
			$itemWithEnLabel->copy(),
		);
		$testCases['identicalLabelMerge'] = array(
			$itemWithEnLabel->copy(),
			$itemWithEnLabel->copy(),
			$emptyItem->copy(),
			$itemWithEnLabel->copy(),
		);

		$itemWithEnBarLabel = ItemBuilder::create()
			->withLabel( 'en', 'bar' )
			->build();

		$itemWithLabelAndAlias = ItemBuilder::create()
			->withLabel( 'en', 'bar' )
			->withAliases( 'en', [ 'foo' ] )
			->build();

		$testCases['labelAsAliasMerge'] = array(
			$itemWithEnLabel->copy(),
			$itemWithEnBarLabel->copy(),
			$emptyItem->copy(),
			$itemWithLabelAndAlias->copy()
		);

		$itemWithDescription = ItemBuilder::create()
			->withDescription( 'en', 'foo' )
			->build();

		$testCases['descriptionMerge'] = array(
			$itemWithDescription->copy(),
			$emptyItem->copy(),
			$emptyItem->copy(),
			$itemWithDescription->copy(),
		);
		$testCases['identicalDescriptionMerge'] = array(
			$itemWithDescription->copy(),
			$itemWithDescription->copy(),
			$emptyItem->copy(),
			$itemWithDescription->copy(),
		);

		$itemWithBarDescription = ItemBuilder::create()
			->withDescription( 'en', 'bar' )
			->build();
		$testCases['ignoreConflictDescriptionMerge'] = array(
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			array( 'description' )
		);

		$itemWithFooBarAliases = ItemBuilder::create()
			->withAliases( 'en', [ 'foo', 'bar' ] )
			->build();

		$testCases['aliasMerge'] = array(
			$itemWithFooBarAliases->copy(),
			$emptyItem->copy(),
			$emptyItem->copy(),
			$itemWithFooBarAliases->copy(),
		);

		$itemWithFooBarBazAliases = ItemBuilder::create()
			->withAliases( 'en', [ 'foo', 'bar', 'baz' ] )
			->build();

		$testCases['duplicateAliasMerge'] = array(
			$itemWithFooBarAliases->copy(),
			$itemWithFooBarBazAliases->copy(),
			$emptyItem->copy(),
			$itemWithFooBarBazAliases->copy(),
		);

		$itemWithLink = ItemBuilder::create()
			->withSiteLink( 'enwiki', 'foo' )
			->build();

		$testCases['linkMerge'] = array(
			$itemWithLink->copy(),
			$emptyItem->copy(),
			$emptyItem->copy(),
			$itemWithLink->copy(),
		);

		$testCases['sameLinkLinkMerge'] = array(
			$itemWithLink->copy(),
			$itemWithLink->copy(),
			$emptyItem->copy(),
			$itemWithLink->copy(),
		);

		$itemWithBarLink = ItemBuilder::create()
			->withSiteLink( 'enwiki', 'bar' )
			->build();

		$testCases['ignoreConflictLinkMerge'] = array(
			$itemWithLink->copy(),
			$itemWithBarLink->copy(),
			$itemWithLink->copy(),
			$itemWithBarLink->copy(),
			array( 'sitelink' ),
		);

		$statement = new Statement( new PropertyNoValueSnak( new PropertyId( 'P56' ) ) );
		$statement->setGuid( 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556' );

		$itemWithStatement = ItemBuilder::create()
			->withStatement( $statement )
			->build();
		$testCases['statementMerge'] = array(
			$itemWithStatement->copy(),
			$emptyItem->copy(),
			$emptyItem->copy(),
			$itemWithStatement->copy()
		);

		$qualifiedStatement = new Statement(
			new PropertyNoValueSnak( new PropertyId( 'P56' ) ),
			new SnakList( array( new PropertyNoValueSnak( new PropertyId( 'P56' ) ) ) )
		);
		$qualifiedStatement->setGuid( 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556' );

		$itemWithQualifiedStatement = ItemBuilder::create()
			->withStatement( $qualifiedStatement )
			->build();

		$testCases['statementWithQualifierMerge'] = array(
			$itemWithQualifiedStatement->copy(),
			$emptyItem->copy(),
			$emptyItem->copy(),
			$itemWithQualifiedStatement->copy()
		);

		$anotherQualifiedStatement = new Statement(
			new PropertyNoValueSnak( new PropertyId( 'P88' ) ),
			new SnakList( array( new PropertyNoValueSnak( new PropertyId( 'P88' ) ) ) )
		);
		$anotherQualifiedStatement->setGuid( 'Q111$D8404CDA-25E4-4334-AF88-A3290BCD9C0F' );

		$selfReferencingStatement = new Statement(
			new PropertyValueSnak( new PropertyId( 'P42' ), new EntityIdValue( new ItemId( 'Q111' ) ) )
		);
		$selfReferencingStatement->setGuid( 'Q111$D74D43D7-BD8F-4240-A058-24C5171ABBFA' );

		$bigItemBuilder = ItemBuilder::create()
			->withId( 'Q111' )
			->withLabel( 'en', 'foo' )
			->withLabel( 'pt', 'ptfoo' )
			->withDescription( 'en', 'foo' )
			->withDescription( 'pl', 'pldesc' )
			->withAliases( 'en', [ 'foo', 'bar' ] )
			->withAliases( 'de', [ 'defoo', 'debar' ] )
			->withSiteLink( 'dewiki', 'foo' )
			->withStatement( $anotherQualifiedStatement )
			->withStatement( $selfReferencingStatement );

		$testCases['itemMerge'] = array(
			$bigItemBuilder->build(),
			$emptyItem->copy(),
			$emptyItem->copy(),
			$bigItemBuilder->build(),
		);

		$referencingStatement = new Statement(
			new PropertyValueSnak( new PropertyId( 'P42' ), new EntityIdValue( new ItemId( 'Q222' ) ) )
		);
		$referencingStatement->setGuid( 'Q111$949A4D27-0EBC-46A7-BF5F-AA2DD33C0443' );

		$anotherBigItem = $bigItemBuilder->withSiteLink( 'nlwiki', 'bar' )
			->withStatement( $referencingStatement )
			->build();

		$smallerItem = ItemBuilder::create()
			->withId( 'Q222' )
			->withLabel( 'en', 'toLabel' )
			->withDescription( 'pl', 'toDescription' )
			->withSiteLink( 'nlwiki', 'toLink' )
			->build();

		$smallerMergedItem = ItemBuilder::create()
			->withId( 'Q222' )
			->withDescription( 'pl', 'pldesc' )
			->withSiteLink( 'nlwiki', 'bar' )
			->build();

		$bigMergedItem = ItemBuilder::create()
			->withId( 'Q111' )
			->withLabel( 'en', 'toLabel' )
			->withLabel( 'pt', 'ptfoo' )
			->withDescription( 'en', 'foo' )
			->withDescription( 'pl', 'toDescription' )
			->withAliases( 'en', [ 'foo', 'bar' ] )
			->withAliases( 'de', [ 'defoo', 'debar' ] )
			->withSiteLink( 'dewiki', 'foo' )
			->withSiteLink( 'nlwiki', 'toLink' )
			->withStatement( $anotherQualifiedStatement )
			->withStatement( $selfReferencingStatement )
			->withStatement( $referencingStatement )
			->build();

		$testCases['ignoreConflictItemMerge'] = array(
			$anotherBigItem->copy(),
			$smallerItem->copy(),
			$smallerMergedItem->copy(),
			$bigMergedItem->copy(),
			array( 'description', 'sitelink', 'statement' )
		);

		return $testCases;
	}

	public function testSitelinkConflictNormalization() {
		$from = ItemBuilder::create()
			->withId( 'Q111' )
			->withSiteLink( 'enwiki', 'FOo' )
			->build();

		$to = ItemBuilder::create()
			->withId( 'Q222' )
			->withSiteLink( 'enwiki', 'Foo' )
			->build();

		$enwiki = $this->getMock( Site::class );
		$enwiki->expects( $this->once() )
			->method( 'getGlobalId' )
			->will( $this->returnValue( 'enwiki' ) );
		$enwiki->expects( $this->exactly( 2 ) )
			->method( 'normalizePageName' )
			->withConsecutive(
				array( $this->equalTo( 'FOo' ) ),
				array( $this->equalTo( 'Foo' ) )
			)
			->will( $this->returnValue( 'Foo' ) );

		$mockSiteStore = new HashSiteStore( TestSites::getSites() );
		$mockSiteStore->saveSite( $enwiki );

		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			array(),
			$mockSiteStore
		);

		$changeOps->apply();

		$this->assertFalse( $from->getSiteLinkList()->hasLinkWithSiteId( 'enwiki' ) );
		$this->assertTrue( $to->getSiteLinkList()->hasLinkWithSiteId( 'enwiki' ) );
	}

	public function testExceptionThrownWhenNormalizingSiteNotFound() {
		$from = ItemBuilder::create()
			->withId( 'Q111' )
			->withSiteLink( 'enwiki', 'FOo' )
			->build();

		$to = ItemBuilder::create()
			->withId( 'Q222' )
			->withSiteLink( 'enwiki', 'Foo' )
			->build();


		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			array(),
			new HashSiteStore()
		);

		$this->setExpectedException(
			ChangeOpException::class,
			'Conflicting sitelinks for enwiki, Failed to normalize'
		);

		$changeOps->apply();
	}

	public function testExceptionThrownWhenSitelinkDuplicatesDetected() {
		$from = $this->newItemWithId( 'Q111' );
		$to = $this->newItemWithId( 'Q222' );
		$to->getSiteLinkList()->addNewSiteLink( 'nnwiki', 'DUPE' );

		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to
		);

		$this->setExpectedException( ChangeOpException::class, 'SiteLink conflict' );
		$changeOps->apply();
	}

	public function testExceptionNotThrownWhenSitelinkDuplicatesDetectedOnFromItem() {
		// the from-item keeps the sitelinks
		$from = $this->newItemWithId( 'Q111' );
		$from->getSiteLinkList()->addNewSiteLink( 'nnwiki', 'DUPE' );

		$to = $this->newItemWithId( 'Q222' );
		$to->getSiteLinkList()->addNewSiteLink( 'nnwiki', 'BLOOP' );

		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			array( 'sitelink' )
		);

		$changeOps->apply();
		$this->assertTrue( true ); // no exception thrown
	}

	public function testExceptionThrownWhenFromHasLink() {

		$from = ItemBuilder::create()
			->withId( 'Q111' )
			->withPropertyValueStatement( 'P42', new ItemId( 'Q222' ) )
			->build();

		$to = ItemBuilder::create()->withId( 'Q222' )->build();

		$changeOps = $this->makeChangeOpsMerge( $from, $to );

		$this->setExpectedException(
			ChangeOpException::class,
			'The two items cannot be merged because one of them links to the other using property P42'
		);
		$changeOps->apply();
	}

	public function testExceptionThrownWhenToHasLink() {
		$from = ItemBuilder::create()->withId( 'Q111' )->build();

		$to = ItemBuilder::create()
			->withId( 'Q222' )
			->withPropertyValueStatement( 'P42', new ItemId( 'Q111' ) )
			->build();

		$changeOps = $this->makeChangeOpsMerge( $from, $to );

		$this->setExpectedException(
			ChangeOpException::class,
			'The two items cannot be merged because one of them links to the other using property P42'
		);
		$changeOps->apply();
	}

}
