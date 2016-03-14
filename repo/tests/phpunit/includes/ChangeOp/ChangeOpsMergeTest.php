<?php

namespace Wikibase\Test;

use HashSiteStore;
use InvalidArgumentException;
use MediaWikiTestCase;
use Site;
use TestSites;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\EntityValidator;

/**
 * @covers Wikibase\ChangeOp\ChangeOpsMerge
 *
 * @group Wikibase
 * @group WikibaseRepo
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
				$siteLinks = $item->getSiteLinkList();
				foreach ( $siteLinks as $siteLink ) {
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

		$testCases['labelAsAliasMerge'] = array(
			$itemWithEnLabel->copy(),
			$itemWithEnBarLabel->copy(),
			new Item(),
			$itemWithLabelAndAlias->copy()
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

		$statement = new Statement( new PropertyNoValueSnak( new PropertyId( 'P56' ) ) );
		$statement->setGuid( 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556' );

		$itemWithStatement = new Item();
		$itemWithStatement->getStatements()->addStatement( $statement );
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

		$itemWithQualifiedStatement = new Item();
		$itemWithQualifiedStatement->getStatements()->addStatement( $qualifiedStatement );

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

		$bigItem = new Item();
		$bigItem->setId( 111 );
		$bigItem->getFingerprint()->setLabel( 'en', 'foo' );
		$bigItem->getFingerprint()->setLabel( 'pt', 'ptfoo' );
		$bigItem->getFingerprint()->setDescription( 'en', 'foo' );
		$bigItem->getFingerprint()->setDescription( 'pl', 'pldesc' );
		$bigItem->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar' ) );
		$bigItem->getFingerprint()->setAliasGroup( 'de', array( 'defoo', 'debar' ) );
		$bigItem->getSiteLinkList()->addNewSiteLink( 'dewiki', 'foo' );
		$bigItem->getStatements()->addStatement( $anotherQualifiedStatement );
		$bigItem->getStatements()->addStatement( $selfReferencingStatement );

		$testCases['itemMerge'] = array(
			$bigItem->copy(),
			new Item(),
			new Item(),
			$bigItem->copy(),
		);

		$referencingStatement = new Statement(
			new PropertyValueSnak( new PropertyId( 'P42' ), new EntityIdValue( new ItemId( 'Q222' ) ) )
		);
		$referencingStatement->setGuid( 'Q111$949A4D27-0EBC-46A7-BF5F-AA2DD33C0443' );

		$bigItem->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'bar' );
		$bigItem->getStatements()->addStatement( $referencingStatement );

		$smallerItem = new Item();
		$smallerItem->setId( 222 );
		$smallerItem->getFingerprint()->setLabel( 'en', 'toLabel' );
		$smallerItem->getFingerprint()->setDescription( 'pl', 'toDescription' );
		$smallerItem->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'toLink' );

		$smallerMergedItem = new Item();
		$smallerMergedItem->setId( 222 );
		$smallerMergedItem->getFingerprint()->setDescription( 'pl', 'pldesc' );
		$smallerMergedItem->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'bar' );

		$bigMergedItem = new Item();
		$bigMergedItem->setId( 111 );
		$bigMergedItem->getFingerprint()->setLabel( 'en', 'toLabel' );
		$bigMergedItem->getFingerprint()->setLabel( 'pt', 'ptfoo' );
		$bigMergedItem->getFingerprint()->setDescription( 'en', 'foo' );
		$bigMergedItem->getFingerprint()->setDescription( 'pl', 'toDescription' );
		$bigMergedItem->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar' ) );
		$bigMergedItem->getFingerprint()->setAliasGroup( 'de', array( 'defoo', 'debar' ) );

		$bigMergedItem->getSiteLinkList()->addNewSiteLink( 'dewiki', 'foo' );
		$bigMergedItem->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'toLink' );
		$bigMergedItem->setStatements(
			new StatementList( $anotherQualifiedStatement, $selfReferencingStatement, $referencingStatement )
		);

		$testCases['ignoreConflictItemMerge'] = array(
			$bigItem->copy(),
			$smallerItem->copy(),
			$smallerMergedItem->copy(),
			$bigMergedItem->copy(),
			array( 'description', 'sitelink', 'statement' )
		);

		return $testCases;
	}

	public function testSitelinkConflictNormalization() {
		$from = new Item( new ItemId( 'Q111' ) );
		$from->getSiteLinkList()->addNewSiteLink( 'enwiki', 'FOo' );

		$to = new Item( new ItemId( 'Q222' ) );
		$to->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

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
			new HashSiteStore()
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

	public function testExceptionThrownWhenFromHasLink() {
		$from = new Item( new ItemId( 'Q111' ) );
		$from->getStatements()->addNewStatement(
			new PropertyValueSnak( new PropertyId( 'P42' ), new EntityIdValue( new ItemId( 'Q222' ) ) )
		);

		$to = new Item( new ItemId( 'Q222' ) );

		$changeOps = $this->makeChangeOpsMerge( $from, $to );

		$this->setExpectedException(
			'\Wikibase\ChangeOp\ChangeOpException',
			'The two items cannot be merged because one of them links to the other using property P42'
		);
		$changeOps->apply();
	}

	public function testExceptionThrownWhenToHasLink() {
		$from = new Item( new ItemId( 'Q111' ) );

		$to = new Item( new ItemId( 'Q222' ) );
		$to->getStatements()->addNewStatement(
			new PropertyValueSnak( new PropertyId( 'P42' ), new EntityIdValue( new ItemId( 'Q111' ) ) )
		);

		$changeOps = $this->makeChangeOpsMerge( $from, $to );

		$this->setExpectedException(
			'\Wikibase\ChangeOp\ChangeOpException',
			'The two items cannot be merged because one of them links to the other using property P42'
		);
		$changeOps->apply();
	}

}
