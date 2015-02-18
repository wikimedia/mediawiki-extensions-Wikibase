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
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

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
		array $ignoreConflicts = array()
	) {
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
			$this->mockProvider->getMockTermValidatorFactory()
		);

		return new ChangeOpsMerge(
			$fromItem,
			$toItem,
			$ignoreConflicts,
			$constraintProvider,
			$changeOpFactoryProvider
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
		foreach ( $item->getClaims() as $claim ) {
			$claim->setGuid( null );
		}
	}

	/**
	 * @return array 1=>from 2=>to 3=>expectedFrom 4=>expectedTo
	 */
	public function provideData() {
		$testCases = array();

		$itemWithEnLabel = new Item();
		$itemWithEnLabel->setFingerprint( new Fingerprint( new TermList(
			array( new Term( 'en', 'foo' ) )
		) ) );

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
		$itemWithEnBarLabel->setFingerprint( new Fingerprint(
			new TermList( array( new Term( 'en', 'bar' ) ) )
		)	);

		$itemWithLabelAndAlias = new Item();
		$itemWithLabelAndAlias->setFingerprint( new Fingerprint(
			new TermList( array( new Term( 'en', 'bar' ) ) ),
			null,
			new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) )
		) );

		$testCases['ignoreConflictLabelMerge'] = array(
			$itemWithEnLabel->copy(),
			$itemWithEnBarLabel->copy(),
			new Item(),
			$itemWithLabelAndAlias->copy(),
			array( 'label' )
		);

		$itemWithDescription = new Item();
		$itemWithDescription->setFingerprint( new Fingerprint(
			null,
			new TermList( array( new Term( 'en', 'foo' ) ) )
		) );

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
		$itemWithBarDescription->setFingerprint( new Fingerprint(
			null,
			new TermList( array( new Term( 'en', 'bar' ) ) )
		) );
		$testCases['ignoreConflictDescriptionMerge'] = array(
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			$itemWithDescription->copy(),
			$itemWithBarDescription->copy(),
			array( 'description' )
		);

		$itemWithFooBarAliases = new Item();
		$itemWithFooBarAliases->setFingerprint( new Fingerprint(
			null,
			null,
			new AliasGroupList( array( new AliasGroup( 'en', array( 'foo', 'bar' ) ) ) )
		) );

		$testCases['aliasMerge'] = array(
			$itemWithFooBarAliases->copy(),
			new Item(),
			new Item(),
			$itemWithFooBarAliases->copy(),
		);

		$itemWithFooBarBazAliases = new Item();
		$itemWithFooBarBazAliases->setFingerprint( new Fingerprint(
			null,
			null,
			new AliasGroupList( array( new AliasGroup( 'en', array( 'foo', 'bar', 'baz' ) ) ) )
		) );

		$testCases['duplicateAliasMerge'] = array(
			$itemWithFooBarAliases->copy(),
			$itemWithFooBarBazAliases->copy(),
			new Item(),
			$itemWithFooBarBazAliases->copy(),
		);

		$itemWithLink = new Item();
		$itemWithLink->setSiteLinkList( new SiteLinkList( array(
			new SiteLink( 'enwiki', 'foo' )
		) ) );

		$testCases['linkMerge'] = array(
			$itemWithLink->copy(),
			new Item(),
			new Item(),
			$itemWithLink->copy(),
		);

		$itemWithBarLink = new Item();
		$itemWithBarLink->setSiteLinkList( new SiteLinkList( array(
			new SiteLink( 'enwiki', 'bar' )
		) ) );

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
		$itemWithStatement->setStatements( new StatementList( new Statement( $claim ) ) );
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
		$itemWithQualifiedStatement->setStatements( new StatementList( new Statement( $qualifiedClaim ) ) );

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
		$bigItem->setFingerprint( new Fingerprint(
			new TermList( array( new Term( 'en', 'foo' ), new Term( 'pt', 'ptfoo' ) ) ),
			new TermList( array( new Term( 'en', 'foo' ), new Term( 'pl', 'pldesc' ) ) ),
			new AliasGroupList( array(
				new AliasGroup( 'en', array( 'foo', 'bar' ) ),
				new AliasGroup( 'de', array( 'defoo', 'debar' ) )
			) )
		) );
		$bigItem->setSiteLinkList( new SiteLinkList( array( new SiteLink( 'dewiki', 'foo' ) ) ) );
		$bigItem->setStatements( new StatementList( new Statement( $anotherQualifiedClaim ) ) );

		$testCases['itemMerge'] = array(
			$bigItem->copy(),
			new Item(),
			new Item(),
			$bigItem->copy(),
		);

		$bigItem->setSiteLinkList( new SiteLinkList( array(
			new SiteLink( 'dewiki', 'foo' ),
			new SiteLink( 'plwiki', 'bar' )
		) ) );


		$smallerItem = new Item();
		$smallerItem->setFingerprint( new Fingerprint(
			new TermList( array( new Term( 'en', 'toLabel' ) ) ),
			new TermList( array( new Term( 'pl', 'toLabel' ) ) )
		) );
		$smallerItem->setSiteLinkList( new SiteLinkList( array(
			new SiteLink( 'plwiki', 'toLink' )
		) ) );

		$smallerMergedItem = new Item();
		$smallerMergedItem->setFingerprint( new Fingerprint(
			null,
			new TermList( array( new Term( 'pl', 'pldesc' ) ) )
		) );
		$smallerMergedItem->setSiteLinkList( new SiteLinkList( array( new SiteLink( 'plwiki', 'bar' ) ) ) );

		$bigMergedItem = new Item();
		$bigMergedItem->setFingerprint( new Fingerprint(
			new TermList( array( new Term( 'en', 'toLabel' ), new Term( 'pt', 'ptfoo' ) ) ),
			new TermList( array( new Term( 'en', 'foo' ), new Term( 'pl', 'toLabel' ) ) ),
			new AliasGroupList( array(
				new AliasGroup( 'en', array( 'foo', 'bar' ) ),
				new AliasGroup( 'de', array( 'defoo', 'debar' ) )
			) )
		) );
		$bigMergedItem->setSiteLinkList( new SiteLinkList( array(
			new SiteLink( 'dewiki', 'foo' ),
			new SiteLink( 'plwiki', 'toLink' )
		) ) );
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

	public function testExceptionThrownWhenSitelinkDuplicatesDetected() {
		$from = $this->newItemWithId( 'Q111' );
		$to = $this->newItemWithId( 'Q222' );
		$to->getSiteLinkList()->addNewSiteLink( 'eewiki', 'DUPE' );

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
		$from->getSiteLinkList()->addNewSiteLink( 'eewiki', 'DUPE' );

		$to = $this->newItemWithId( 'Q222' );
		$to->getSiteLinkList()->addNewSiteLink( 'eewiki', 'BLOOP' );

		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			array( 'sitelink' )
		);

		$changeOps->apply();
		$this->assertTrue( true ); // no exception thrown
	}

}
