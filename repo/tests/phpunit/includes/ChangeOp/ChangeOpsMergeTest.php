<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\ChangeOp\ChangeOpsMerge
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
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
			$siteLookup = MockSiteStore::newFromTestSites();
		}
		// A validator which makes sure that no site link is for page 'DUPE'
		$siteLinkUniquenessValidator = $this->getMock( 'Wikibase\Validators\EntityValidator' );
		$siteLinkUniquenessValidator->expects( $this->any() )
			->method( 'validateEntity' )
			->will( $this->returnCallback( function( Item $item ) {
					$siteLinks = $item->getSiteLinkList();
					foreach ( $siteLinks as $siteLink ) {
						if ( $siteLink->getPageName() === 'DUPE' ) {
							return Result::newError( array( Error::newError( 'SiteLink conflict' ) ) );
						}
					}
					return Result::newSuccess();
				} ) );

		$constraintProvider = $this->getMockBuilder( 'Wikibase\Validators\EntityConstraintProvider' )
			->disableOriginalConstructor()
			->getMock();
		$constraintProvider->expects( $this->any() )
			->method( 'getUpdateValidators' )
			->will( $this->returnValue( array( $siteLinkUniquenessValidator ) ) );

		$changeOpFactoryProvider = new ChangeOpFactoryProvider(
			$constraintProvider,
			$this->mockProvider->getMockGuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $toItem->getId() ),
			$this->mockProvider->getMockSnakValidator(),
			$this->mockProvider->getMockTermValidatorFactory(),
			$siteLookup
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
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOpsMerge', $changeOps );
	}

	public function provideValidConstruction() {
		$from = $this->newItemWithId( 'Q111' );
		$to = $this->newItemWithId( 'Q222' );
		return array(
			array( $from, $to, array() ),
			array( $from, $to, array( 'label' ) ),
			array( $from, $to, array( 'description' ) ),
			array( $from, $to, array( 'description', 'label' ) ),
			array( $from, $to, array( 'description', 'label', 'sitelink' ) ),
		);
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidIgnoreConflicts( Item $from, Item $to, array $ignoreConflicts ) {
		$this->setExpectedException( 'InvalidArgumentException' );
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
			array( $from, $to, array( 'foo' ) ),
			array( $from, $to, array( 'label', 'foo' ) ),
		);
	}

	private function newItemWithId( $idString ) {
		return new Item( new ItemId( $idString ) );
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

		$itemWithEnLabel = new Item();
		$itemWithEnLabel->getFingerprint()->setLabel( 'en', 'foo' );

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

		$itemWithEnBarLabel = new Item();
		$itemWithEnBarLabel->getFingerprint()->setLabel( 'en', 'bar' );

		$itemWithLabelAndAlias = new Item();
		$itemWithLabelAndAlias->getFingerprint()->setLabel( 'en', 'bar' );
		$itemWithLabelAndAlias->getFingerprint()->setAliasGroup( 'en', array( 'foo' ) );

		$testCases['ignoreConflictLabelMerge'] = array(
			$itemWithEnLabel->copy(),
			$itemWithEnBarLabel->copy(),
			new Item(),
			$itemWithLabelAndAlias->copy(),
			array( 'label' )
		);

		$itemWithDescription = new Item();
		$itemWithDescription->getFingerprint()->setDescription( 'en', 'foo' );

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

		$itemWithBarDescription = new Item();
		$itemWithBarDescription->getFingerprint()->setDescription( 'en', 'bar' );
		$testCases['ignoreConflictDescriptionMerge'] = array(
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			array( 'description' )
		);

		$itemWithFooBarAliases = new Item();
		$itemWithFooBarAliases->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar' ) );

		$testCases['aliasMerge'] = array(
			$itemWithFooBarAliases->copy(),
			new Item(),
			new Item(),
			$itemWithFooBarAliases->copy(),
		);

		$itemWithFooBarBazAliases = new Item();
		$itemWithFooBarBazAliases->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar', 'baz' ) );

		$testCases['duplicateAliasMerge'] = array(
			$itemWithFooBarAliases->copy(),
			$itemWithFooBarBazAliases->copy(),
			new Item(),
			$itemWithFooBarBazAliases->copy(),
		);

		$itemWithLink = new Item();
		$itemWithLink->getSiteLinkList()->addNewSiteLink( 'enwiki', 'foo' );

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

		$itemWithBarLink = new Item();
		$itemWithBarLink->getSiteLinkList()->addNewSiteLink( 'enwiki', 'bar' );

		$testCases['ignoreConflictLinkMerge'] = array(
			$itemWithLink->copy(),
			$itemWithBarLink->copy(),
			$itemWithLink->copy(),
			$itemWithBarLink->copy(),
			array( 'sitelink' ),
		);

		$claim = new Claim( new PropertyNoValueSnak( new PropertyId( 'P56' ) ) );
		$claim->setGuid( 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556' );

		$itemWithStatement = new Item();
		$itemWithStatement->getStatements()->addStatement( new Statement( $claim ) );
		$testCases['claimMerge'] = array(
			$itemWithStatement->copy(),
			new Item(),
			new Item(),
			$itemWithStatement->copy()
		);

		$qualifiedClaim = new Claim(
			new PropertyNoValueSnak( new PropertyId( 'P56' ) ),
			new SnakList( array( new PropertyNoValueSnak( new PropertyId( 'P56' ) ) ) )
		);
		$qualifiedClaim->setGuid( 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556' );

		$itemWithQualifiedStatement = new Item();
		$itemWithQualifiedStatement->getStatements()->addStatement( new Statement( $qualifiedClaim ) );

		$testCases['claimWithQualifierMerge'] = array(
			$itemWithQualifiedStatement->copy(),
			new Item(),
			new Item(),
			$itemWithQualifiedStatement->copy()
		);

		$anotherQualifiedClaim = new Claim(
			new PropertyNoValueSnak( new PropertyId( 'P88' ) ),
			new SnakList( array( new PropertyNoValueSnak( new PropertyId( 'P88' ) ) ) )
		);
		$anotherQualifiedClaim->setGuid( 'Q111$D8404CDA-25E4-4334-AF88-A3290BCD9C0F' );

		$bigItem = new Item();
		$bigItem->getFingerprint()->setLabel( 'en', 'foo' );
		$bigItem->getFingerprint()->setLabel( 'pt', 'ptfoo' );
		$bigItem->getFingerprint()->setDescription( 'en', 'foo' );
		$bigItem->getFingerprint()->setDescription( 'pl', 'pldesc' );
		$bigItem->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar' ) );
		$bigItem->getFingerprint()->setAliasGroup( 'de', array( 'defoo', 'debar' ) );
		$bigItem->getSiteLinkList()->addNewSiteLink( 'dewiki', 'foo' );
		$bigItem->getStatements()->addStatement( new Statement( $anotherQualifiedClaim ) );

		$testCases['itemMerge'] = array(
			$bigItem->copy(),
			new Item(),
			new Item(),
			$bigItem->copy(),
		);

		$bigItem->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'bar' );


		$smallerItem = new Item();
		$smallerItem->getFingerprint()->setLabel( 'en', 'toLabel' );
		$smallerItem->getFingerprint()->setDescription( 'pl', 'toLabel' ); // FIXME: this is not a label
		$smallerItem->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'toLink' );

		$smallerMergedItem = new Item();
		$smallerMergedItem->getFingerprint()->setDescription( 'pl', 'pldesc' );
		$smallerMergedItem->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'bar' );

		$bigMergedItem = new Item();
		$bigMergedItem->getFingerprint()->setLabel( 'en', 'toLabel' );
		$bigMergedItem->getFingerprint()->setLabel( 'pt', 'ptfoo' );
		$bigMergedItem->getFingerprint()->setDescription( 'en', 'foo' );
		$bigMergedItem->getFingerprint()->setDescription( 'pl', 'toLabel' );
		$bigMergedItem->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar' ) );
		$bigMergedItem->getFingerprint()->setAliasGroup( 'de', array( 'defoo', 'debar' ) );

		$bigMergedItem->getSiteLinkList()->addNewSiteLink( 'dewiki', 'foo' );
		$bigMergedItem->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'toLink' );
		$bigMergedItem->setStatements( new StatementList( new Statement( $anotherQualifiedClaim ) ) );

		$testCases['ignoreConflictItemMerge'] = array(
			$bigItem->copy(),
			$smallerItem->copy(),
			$smallerMergedItem->copy(),
			$bigMergedItem->copy(),
			array( 'label', 'description', 'sitelink' )
		);
		return $testCases;
	}

	public function testSitelinkConflictNormalization() {
		$from = new Item( new ItemId( 'Q111' ) );
		$expectedFrom = clone $from;
		$from->getSiteLinkList()->addNewSiteLink( 'enwiki', 'FOo' );

		$to = new Item( new ItemId( 'Q222' ) );
		$expectedTo = clone $to;
		$to->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$enwiki = $this->getMock( 'Site' );
		$enwiki->expects( $this->once() )
			->method( 'getGlobalId' )
			->will( $this->returnValue( 'enwiki' ) );
		$enwiki->expects( $this->exactly( 2 ) )
			->method( 'normalizePageName' )
			->will( $this->returnValue( 'Foo' ) );
		$mockSiteStore = MockSiteStore::newFromTestSites();
		$mockSiteStore->saveSite( $enwiki );

		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			array(),
			$mockSiteStore
		);

		$changeOps->apply();

		$this->assertTrue( $from->equals( $expectedFrom ) );
		$this->assertTrue( $to->equals( $expectedTo ) );
	}

	public function testExceptionThrownWhenNormalizingSiteNotFound() {
		$from = new Item();
		$from->setId( new ItemId( 'Q111' ) );
		$from->getSiteLinkList()->addNewSiteLink( 'enwiki', 'FOo' );
		$to = new Item();
		$to->setId( new ItemId( 'Q222' ) );
		$to->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			array(),
			new MockSiteStore()
		);

		$this->setExpectedException(
			'\Wikibase\ChangeOp\ChangeOpException',
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

		$this->setExpectedException(
			'\Wikibase\ChangeOp\ChangeOpException',
			'SiteLink conflict'
		);
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

}
