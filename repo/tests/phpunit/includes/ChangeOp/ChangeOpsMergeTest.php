<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use HashSiteStore;
use InvalidArgumentException;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Site;
use TestSites;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOpsMerge;
use Wikibase\Repo\Merge\StatementsMerger;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\EntityValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpsMerge
 *
 * @group Wikibase
 * @group ChangeOp
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ChangeOpsMergeTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	private $mockProvider;

	/**
	 * @var StatementsMerger|MockObject
	 */
	private $statementsMerger;

	/**
	 * @param string|null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
		$this->statementsMerger = $this->createMock( StatementsMerger::class );
	}

	protected function makeChangeOpsMerge(
		Item $fromItem,
		Item $toItem,
		array $ignoreConflicts = [],
		$siteLookup = null
	) {
		if ( $siteLookup === null ) {
			$siteLookup = new HashSiteStore( TestSites::getSites() );
		}
		// A validator which makes sure that no site link is for page 'DUPE'
		$siteLinkUniquenessValidator = $this->createMock( EntityValidator::class );
		$siteLinkUniquenessValidator->method( 'validateEntity' )
			->willReturnCallback( function( Item $item ) {
				foreach ( $item->getSiteLinkList()->toArray() as $siteLink ) {
					if ( $siteLink->getPageName() === 'DUPE' ) {
						return Result::newError( [ Error::newError( 'SiteLink conflict' ) ] );
					}
				}
				return Result::newSuccess();
			} );

		$constraintProvider = $this->createMock( EntityConstraintProvider::class );
		$constraintProvider->method( 'getUpdateValidators' )
			->willReturn( [ $siteLinkUniquenessValidator ] );

		$changeOpFactoryProvider = new ChangeOpFactoryProvider(
			$constraintProvider,
			new GuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $toItem->getId() ),
			$this->mockProvider->getMockSnakValidator(),
			$this->mockProvider->getMockTermValidatorFactory(),
			$siteLookup,
			$this->mockProvider->getMockSnakNormalizer(),
			$this->mockProvider->getMockReferenceNormalizer(),
			$this->mockProvider->getMockStatementNormalizer(),
			[],
			true
		);

		return new ChangeOpsMerge(
			$fromItem,
			$toItem,
			$ignoreConflicts,
			$constraintProvider,
			$changeOpFactoryProvider,
			$siteLookup,
			$this->statementsMerger
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
		return [
			[ $from, $to, [] ],
			[ $from, $to, [ 'sitelink' ] ],
			[ $from, $to, [ 'statement' ] ],
			[ $from, $to, [ 'description' ] ],
			[ $from, $to, [ 'description', 'sitelink' ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidIgnoreConflicts( Item $from, Item $to, array $ignoreConflicts ) {
		$this->expectException( InvalidArgumentException::class );
		$this->makeChangeOpsMerge(
			$from,
			$to,
			$ignoreConflicts
		);
	}

	public function provideInvalidConstruction() {
		$from = $this->newItemWithId( 'Q111' );
		$to = $this->newItemWithId( 'Q222' );
		return [
			[ $from, $to, [ 'label' ] ],
			[ $from, $to, [ 'foo' ] ],
			[ $from, $to, [ 'description', 'foo' ] ],
		];
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
		array $ignoreConflicts = []
	) {
		$this->statementsMerger = $this->newRealStatementsMerger();

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
		$testCases = [];

		$itemWithEnLabel = NewItem::withLabel( 'en', 'foo' )
			->build();

		$testCases['labelMerge'] = [
			$itemWithEnLabel->copy(),
			new Item(),
			new Item(),
			$itemWithEnLabel->copy(),
		];
		$testCases['identicalLabelMerge'] = [
			$itemWithEnLabel->copy(),
			$itemWithEnLabel->copy(),
			new Item(),
			$itemWithEnLabel->copy(),
		];

		$itemWithEnBarLabel = NewItem::withLabel( 'en', 'bar' )
			->build();

		$itemWithLabelAndAlias = NewItem::withLabel( 'en', 'bar' )
			->andAliases( 'en', [ 'foo' ] )
			->build();

		$testCases['labelAsAliasMerge'] = [
			$itemWithEnLabel->copy(),
			$itemWithEnBarLabel->copy(),
			new Item(),
			$itemWithLabelAndAlias->copy(),
		];

		$itemWithDescription = NewItem::withDescription( 'en', 'foo' )
			->build();

		$testCases['descriptionMerge'] = [
			$itemWithDescription->copy(),
			new Item(),
			new Item(),
			$itemWithDescription->copy(),
		];
		$testCases['identicalDescriptionMerge'] = [
			$itemWithDescription->copy(),
			$itemWithDescription->copy(),
			new Item(),
			$itemWithDescription->copy(),
		];

		$itemWithBarDescription = NewItem::withDescription( 'en', 'bar' )
			->build();
		$testCases['ignoreConflictDescriptionMerge'] = [
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			[ 'description' ],
		];

		$itemWithFooBarAliases = NewItem::withAliases( 'en', [ 'foo', 'bar' ] )
			->build();

		$testCases['aliasMerge'] = [
			$itemWithFooBarAliases->copy(),
			new Item(),
			new Item(),
			$itemWithFooBarAliases->copy(),
		];

		$itemWithFooBarBazAliases = NewItem::withAliases( 'en', [ 'foo', 'bar', 'baz' ] )
			->build();

		$testCases['duplicateAliasMerge'] = [
			$itemWithFooBarAliases->copy(),
			$itemWithFooBarBazAliases->copy(),
			new Item(),
			$itemWithFooBarBazAliases->copy(),
		];

		$itemWithLink = NewItem::withSiteLink( 'enwiki', 'foo' )
			->build();

		$testCases['linkMerge'] = [
			$itemWithLink->copy(),
			new Item(),
			new Item(),
			$itemWithLink->copy(),
		];

		$testCases['sameLinkLinkMerge'] = [
			$itemWithLink->copy(),
			$itemWithLink->copy(),
			new Item(),
			$itemWithLink->copy(),
		];

		$itemWithBarLink = NewItem::withSiteLink( 'enwiki', 'bar' )
			->build();

		$testCases['ignoreConflictLinkMerge'] = [
			$itemWithLink->copy(),
			$itemWithBarLink->copy(),
			$itemWithLink->copy(),
			$itemWithBarLink->copy(),
			[ 'sitelink' ],
		];

		$statement = new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P56' ) ) );
		$statement->setGuid( 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556' );

		$itemWithStatement = NewItem::withStatement( $statement )
			->build();
		$testCases['statementMerge'] = [
			$itemWithStatement->copy(),
			new Item(),
			new Item(),
			$itemWithStatement->copy(),
		];

		$qualifiedStatement = new Statement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P56' ) ),
			new SnakList( [ new PropertyNoValueSnak( new NumericPropertyId( 'P56' ) ) ] )
		);
		$qualifiedStatement->setGuid( 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556' );

		$itemWithQualifiedStatement = NewItem::withStatement( $qualifiedStatement )
			->build();

		$testCases['statementWithQualifierMerge'] = [
			$itemWithQualifiedStatement->copy(),
			new Item(),
			new Item(),
			$itemWithQualifiedStatement->copy(),
		];

		$anotherQualifiedStatement = new Statement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P88' ) ),
			new SnakList( [ new PropertyNoValueSnak( new NumericPropertyId( 'P88' ) ) ] )
		);
		$anotherQualifiedStatement->setGuid( 'Q111$D8404CDA-25E4-4334-AF88-A3290BCD9C0F' );

		$selfReferencingStatement = new Statement(
			new PropertyValueSnak( new NumericPropertyId( 'P42' ), new EntityIdValue( new ItemId( 'Q111' ) ) )
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

		$testCases['itemMerge'] = [
			$bigItemBuilder->build(),
			new Item(),
			new Item(),
			$bigItemBuilder->build(),
		];

		$referencingStatement = new Statement(
			new PropertyValueSnak( new NumericPropertyId( 'P42' ), new EntityIdValue( new ItemId( 'Q222' ) ) )
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

		$testCases['ignoreConflictItemMerge'] = [
			$anotherBigItem,
			$smallerItem,
			$smallerMergedItem,
			$bigMergedItem,
			[ 'description', 'sitelink', 'statement' ],
		];

		return $testCases;
	}

	public function testSitelinkConflictNormalization() {
		$from = NewItem::withId( 'Q111' )
			->andSiteLink( 'enwiki', 'FOo' )
			->build();

		$to = NewItem::withId( 'Q222' )
			->andSiteLink( 'enwiki', 'Foo' )
			->build();

		$enwiki = $this->createMock( Site::class );
		$enwiki->expects( $this->once() )
			->method( 'getGlobalId' )
			->willReturn( 'enwiki' );
		$enwiki->expects( $this->exactly( 2 ) )
			->method( 'normalizePageName' )
			->withConsecutive(
				[ 'FOo' ],
				[ 'Foo' ]
			)
			->willReturn( 'Foo' );

		$mockSiteStore = new HashSiteStore( TestSites::getSites() );
		$mockSiteStore->saveSite( $enwiki );

		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			[],
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
			[],
			new HashSiteStore()
		);

		$this->expectException( ChangeOpException::class );
		$this->expectExceptionMessage( 'Conflicting sitelinks for enwiki, Failed to normalize' );

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

		$this->expectException( ChangeOpException::class );
		$this->expectExceptionMessage( 'SiteLink conflict' );
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
			[ 'sitelink' ]
		);

		$changeOps->apply();
		$this->assertTrue( true ); // no exception thrown
	}

	public function testExceptionThrownWhenFromHasLink() {
		$from = NewItem::withId( 'Q111' )
			->andStatement(
				NewStatement::forProperty( 'P42' )->withValue( new ItemId( 'Q222' ) )
			)
			->build();

		$to = NewItem::withId( 'Q222' )
			->build();

		$changeOps = $this->makeChangeOpsMerge( $from, $to );

		$this->expectException( ChangeOpException::class );
		$this->expectExceptionMessage( 'The two items cannot be merged because one of them links to the other using the properties: P42' );
		$changeOps->apply();
	}

	public function testExceptionThrownWhenToHasLink() {
		$from = NewItem::withId( 'Q111' )
			->build();

		$to = NewItem::withId( 'Q222' )
			->andStatement(
				NewStatement::forProperty( 'P42' )->withValue( new ItemId( 'Q111' ) )
			)
			->build();

		$changeOps = $this->makeChangeOpsMerge( $from, $to );

		$this->expectException( ChangeOpException::class );
		$this->expectExceptionMessage( 'The two items cannot be merged because one of them links to the other using the properties: P42' );
		$changeOps->apply();
	}

	private function newRealStatementsMerger() {
		return WikibaseRepo::getChangeOpFactoryProvider()
			->getMergeFactory()
			->getStatementsMerger();
	}

}
