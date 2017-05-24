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
use Wikibase\Repo\Tests\NewItem;
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
		return NewItem::withId( $idString )
			->build();
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

		$itemWithEnLabel = NewItem::withLabel( 'en', 'foo' )
			->build();

		$testCases['labelMerge'] = array(
			$itemWithEnLabel->copy(),
			new Item(),
			new Item(),
			$itemWithEnLabel->copy(),
		);
		$testCases['identicalLabelMerge'] = array(
			$itemWithEnLabel->copy(),
			$itemWithEnLabel->copy(),
			new Item(),
			$itemWithEnLabel->copy(),
		);

		$itemWithEnBarLabel = NewItem::withLabel( 'en', 'bar' )
			->build();

		$itemWithLabelAndAlias = NewItem::withLabel( 'en', 'bar' )
			->andAliases( 'en', [ 'foo' ] )
			->build();

		$testCases['labelAsAliasMerge'] = array(
			$itemWithEnLabel->copy(),
			$itemWithEnBarLabel->copy(),
			new Item(),
			$itemWithLabelAndAlias->copy()
		);

		$itemWithDescription = NewItem::withDescription( 'en', 'foo' )
			->build();

		$testCases['descriptionMerge'] = array(
			$itemWithDescription->copy(),
			new Item(),
			new Item(),
			$itemWithDescription->copy(),
		);
		$testCases['identicalDescriptionMerge'] = array(
			$itemWithDescription->copy(),
			$itemWithDescription->copy(),
			new Item(),
			$itemWithDescription->copy(),
		);

		$itemWithBarDescription = NewItem::withDescription( 'en', 'bar' )
			->build();
		$testCases['ignoreConflictDescriptionMerge'] = array(
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			array( 'description' )
		);

		$itemWithFooBarAliases = NewItem::withAliases( 'en', [ 'foo', 'bar' ] )
			->build();

		$testCases['aliasMerge'] = array(
			$itemWithFooBarAliases->copy(),
			new Item(),
			new Item(),
			$itemWithFooBarAliases->copy(),
		);

		$itemWithFooBarBazAliases = NewItem::withAliases( 'en', [ 'foo', 'bar', 'baz' ] )
			->build();

		$testCases['duplicateAliasMerge'] = array(
			$itemWithFooBarAliases->copy(),
			$itemWithFooBarBazAliases->copy(),
			new Item(),
			$itemWithFooBarBazAliases->copy(),
		);

		$itemWithLink = NewItem::withSiteLink( 'enwiki', 'foo' )
			->build();

		$testCases['linkMerge'] = array(
			$itemWithLink->copy(),
			new Item(),
			new Item(),
			$itemWithLink->copy(),
		);

		$testCases['sameLinkLinkMerge'] = array(
			$itemWithLink->copy(),
			$itemWithLink->copy(),
			new Item(),
			$itemWithLink->copy(),
		);

		$itemWithBarLink = NewItem::withSiteLink( 'enwiki', 'bar' )
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

		$itemWithStatement = NewItem::withStatement( $statement )
			->build();
		$testCases['statementMerge'] = array(
			$itemWithStatement->copy(),
			new Item(),
			new Item(),
			$itemWithStatement->copy()
		);

		$qualifiedStatement = new Statement(
			new PropertyNoValueSnak( new PropertyId( 'P56' ) ),
			new SnakList( array( new PropertyNoValueSnak( new PropertyId( 'P56' ) ) ) )
		);
		$qualifiedStatement->setGuid( 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556' );

		$itemWithQualifiedStatement = NewItem::withStatement( $qualifiedStatement )
			->build();

		$testCases['statementWithQualifierMerge'] = array(
			$itemWithQualifiedStatement->copy(),
			new Item(),
			new Item(),
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

		$bigItemBuilder = NewItem::withId( 'Q111' )
			->andLabel( 'en', 'foo' )
			->andLabel( 'pt', 'ptfoo' )
			->andDescription( 'en', 'foo' )
			->andDescription( 'pl', 'pldesc' )
			->andAliases( 'en', [ 'foo', 'bar' ] )
			->andAliases( 'de', [ 'defoo', 'debar' ] )
			->andSiteLink( 'dewiki', 'foo' )
			->andStatement( $anotherQualifiedStatement )
			->andStatement( $selfReferencingStatement );

		$testCases['itemMerge'] = array(
			$bigItemBuilder->build(),
			new Item(),
			new Item(),
			$bigItemBuilder->build(),
		);

		$referencingStatement = new Statement(
			new PropertyValueSnak( new PropertyId( 'P42' ), new EntityIdValue( new ItemId( 'Q222' ) ) )
		);
		$referencingStatement->setGuid( 'Q111$949A4D27-0EBC-46A7-BF5F-AA2DD33C0443' );

		$anotherBigItem = $bigItemBuilder->andSiteLink( 'nlwiki', 'bar' )
			->andStatement( $referencingStatement )
			->build();

		$smallerItem = NewItem::withId( 'Q222' )
			->andLabel( 'en', 'toLabel' )
			->andDescription( 'pl', 'toDescription' )
			->andSiteLink( 'nlwiki', 'toLink' )
			->build();

		$smallerMergedItem = NewItem::withId( 'Q222' )
			->andDescription( 'pl', 'pldesc' )
			->andSiteLink( 'nlwiki', 'bar' )
			->build();

		$bigMergedItem = NewItem::withId( 'Q111' )
			->andLabel( 'en', 'toLabel' )
			->andLabel( 'pt', 'ptfoo' )
			->andDescription( 'en', 'foo' )
			->andDescription( 'pl', 'toDescription' )
			->andAliases( 'en', [ 'foo', 'bar' ] )
			->andAliases( 'de', [ 'defoo', 'debar' ] )
			->andSiteLink( 'dewiki', 'foo' )
			->andSiteLink( 'nlwiki', 'toLink' )
			->andStatement( $anotherQualifiedStatement )
			->andStatement( $selfReferencingStatement )
			->andStatement( $referencingStatement )
			->build();

		$testCases['ignoreConflictItemMerge'] = array(
			$anotherBigItem,
			$smallerItem,
			$smallerMergedItem,
			$bigMergedItem,
			array( 'description', 'sitelink', 'statement' )
		);

		return $testCases;
	}

	public function testSitelinkConflictNormalization() {
		$from = NewItem::withId( 'Q111' )
			->andSiteLink( 'enwiki', 'FOo' )
			->build();

		$to = NewItem::withId( 'Q222' )
			->andSiteLink( 'enwiki', 'Foo' )
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
		$from = NewItem::withId( 'Q111' )
			->andSiteLink( 'enwiki', 'FOo' )
			->build();

		$to = NewItem::withId( 'Q222' )
			->andSiteLink( 'enwiki', 'Foo' )
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

		$from = NewItem::withId( 'Q111' )
			->andPropertyValueSnak( 'P42', new ItemId( 'Q222' ) )
			->build();

		$to = NewItem::withId( 'Q222' )
			->build();

		$changeOps = $this->makeChangeOpsMerge( $from, $to );

		$this->setExpectedException(
			ChangeOpException::class,
			'The two items cannot be merged because one of them links to the other using property P42'
		);
		$changeOps->apply();
	}

	public function testExceptionThrownWhenToHasLink() {
		$from = NewItem::withId( 'Q111' )
			->build();

		$to = NewItem::withId( 'Q222' )
			->andPropertyValueSnak( 'P42', new ItemId( 'Q111' ) )
			->build();

		$changeOps = $this->makeChangeOpsMerge( $from, $to );

		$this->setExpectedException(
			ChangeOpException::class,
			'The two items cannot be merged because one of them links to the other using property P42'
		);
		$changeOps->apply();
	}

}
