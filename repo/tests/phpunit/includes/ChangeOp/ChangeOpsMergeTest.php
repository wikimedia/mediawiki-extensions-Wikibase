<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\InternalSerialization\DeserializerFactory;

/**
 * @covers Wikibase\ChangeOp\ChangeOpsMerge
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpsMergeTest extends PHPUnit_Framework_TestCase {

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

	/**
	 * @param string $id
	 * @param array $data
	 *
	 * @return Item
	 */
	private function getItem( $id, array $data = array() ) {
		$deserializer = $this->getEntityDeserializer();
		$item = $deserializer->deserialize( $data );
		$item->setId( new ItemId( $id ) );
		return $item;
	}

	private function newItemWithId( $idString ) {
		return new Item( new ItemId( $idString ) );
	}

	/**
	 * @dataProvider provideData
	 */
	public function testCanApply( array $fromData, array $toData, $expectedFromData, $expectedToData, array $ignoreConflicts = array() ) {
		$from = $this->getItem( 'Q111', $fromData );
		$to = $this->getItem( 'Q222', $toData );

		$changeOps = $this->makeChangeOpsMerge(
			$from,
			$to,
			$ignoreConflicts
		);

		$deserializer = $this->getEntityDeserializer();

		$this->assertTrue( $from->equals( $deserializer->deserialize( $fromData ) ), 'FromItem was not filled correctly' );
		$this->assertTrue( $to->equals( $deserializer->deserialize( $toData ) ), 'ToItem was not filled correctly' );

		$changeOps->apply();

		$expectedFrom = $deserializer->deserialize( $expectedFromData );
		$expectedTo = $deserializer->deserialize( $expectedToData );

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
	 * @return array 1=>fromData 2=>toData 3=>expectedFromData 4=>expectedToData
	 */
	public function provideData() {
		$testCases = array();
		$testCases['labelMerge'] = array(
			array( 'label' => array( 'en' => 'foo' ) ),
			array(),
			array(),
			array( 'label' => array( 'en' => 'foo' ) ),
		);
		$testCases['identicalLabelMerge'] = array(
			array( 'label' => array( 'en' => 'foo' ) ),
			array( 'label' => array( 'en' => 'foo' ) ),
			array(),
			array( 'label' => array( 'en' => 'foo' ) ),
		);
		$testCases['ignoreConflictLabelMerge'] = array(
			array( 'label' => array( 'en' => 'foo' ) ),
			array( 'label' => array( 'en' => 'bar' ) ),
			array( 'label' => array( ) ),
			array(
				'label' => array( 'en' => 'bar' ),
				'aliases' => array( 'en' => array( 'foo' ) )
			),
			array( 'label' )
		);
		$testCases['descriptionMerge'] = array(
			array( 'description' => array( 'en' => 'foo' ) ),
			array(),
			array(),
			array( 'description' => array( 'en' => 'foo' ) ),
		);
		$testCases['identicalDescriptionMerge'] = array(
			array( 'description' => array( 'en' => 'foo' ) ),
			array( 'description' => array( 'en' => 'foo' ) ),
			array(),
			array( 'description' => array( 'en' => 'foo' ) ),
		);
		$testCases['ignoreConflictDescriptionMerge'] = array(
			array( 'description' => array( 'en' => 'foo' ) ),
			array( 'description' => array( 'en' => 'bar' ) ),
			array( 'description' => array( 'en' => 'foo' ) ),
			array( 'description' => array( 'en' => 'bar' ) ),
			array( 'description' )
		);
		$testCases['aliasMerge'] = array(
			array( 'aliases' => array( 'en' => array( 'foo', 'bar' ) ) ),
			array(),
			array(),
			array( 'aliases' => array( 'en' => array( 'foo', 'bar' ) ) ),
		);
		$testCases['duplicateAliasMerge'] = array(
			array( 'aliases' => array( 'en' => array( 'foo', 'bar' ) ) ),
			array( 'aliases' => array( 'en' => array( 'foo', 'bar', 'baz' ) ) ),
			array(),
			array( 'aliases' => array( 'en' => array( 'foo', 'bar', 'baz' ) ) ),
		);
		$testCases['linkMerge'] = array(
			array( 'links' => array( 'enwiki' => array( 'name' => 'foo', 'badges' => array() ) ) ),
			array(),
			array(),
			array( 'links' => array( 'enwiki' => array( 'name' => 'foo', 'badges' => array() ) ) ),
		);
		$testCases['ignoreConflictLinkMerge'] = array(
			array( 'links' => array( 'enwiki' => array( 'name' => 'foo', 'badges' => array() ) ) ),
			array( 'links' => array( 'enwiki' => array( 'name' => 'bar', 'badges' => array() ) ) ),
			array( 'links' => array( 'enwiki' => array( 'name' => 'foo', 'badges' => array() ) ) ),
			array( 'links' => array( 'enwiki' => array( 'name' => 'bar', 'badges' => array() ) ) ),
			array( 'sitelink' ),
		);
		$testCases['claimMerge'] = array(
			array( 'claims' => array(
				array(
					'm' => array( 'novalue', 56 ),
					'q' => array( ),
					'g' => 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556',
					'refs' => array(),
					'rank' => Statement::RANK_NORMAL,
				)
			),
			),
			array(),
			array(),
			array( 'claims' => array(
				array(
					'm' => array( 'novalue', 56 ),
					'q' => array( ),
					'g' => 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556',
					'refs' => array(),
					'rank' => Statement::RANK_NORMAL,
				)
			),
			),
		);
		$testCases['claimWithQualifierMerge'] = array(
			array( 'claims' => array(
				array(
					'm' => array( 'novalue', 56 ),
					'q' => array( array(  'novalue', 56  ) ),
					'g' => 'Q111$D8404CDA-25E4-4334-AF13-A3290BCD9C0F',
					'refs' => array(),
					'rank' => Statement::RANK_NORMAL,
				)
			),
			),
			array(),
			array(),
			array( 'claims' => array(
				array(
					'm' => array( 'novalue', 56 ),
					'q' => array( array(  'novalue', 56  ) ),
					'g' => 'Q111$D8404CDA-25E4-4334-AF13-A3290BCD9C0F',
					'refs' => array(),
					'rank' => Statement::RANK_NORMAL,
				)
			),
			),
		);
		$testCases['itemMerge'] = array(
			array(
				'label' => array( 'en' => 'foo', 'pt' => 'ptfoo' ),
				'description' => array( 'en' => 'foo', 'pl' => 'pldesc' ),
				'aliases' => array( 'en' => array( 'foo', 'bar' ), 'de' => array( 'defoo', 'debar' ) ),
				'links' => array( 'dewiki' => array( 'name' => 'foo', 'badges' => array() ) ),
				'claims' => array(
					array(
						'm' => array( 'novalue', 88 ),
						'q' => array( array( 'novalue', 88 ) ),
						'g' => 'Q111$D8404CDA-25E4-4334-AF88-A3290BCD9C0F',
						'refs' => array(),
						'rank' => Statement::RANK_NORMAL,
					)
				),
			),
			array(),
			array(),
			array(
				'label' => array( 'en' => 'foo', 'pt' => 'ptfoo' ),
				'description' => array( 'en' => 'foo', 'pl' => 'pldesc' ),
				'aliases' => array( 'en' => array( 'foo', 'bar' ), 'de' => array( 'defoo', 'debar' ) ),
				'links' => array( 'dewiki' => array( 'name' => 'foo', 'badges' => array() ) ),
				'claims' => array(
					array(
						'm' => array( 'novalue', 88 ),
						'q' => array( array( 'novalue', 88 ) ),
						'g' => 'Q111$D8404CDA-25E4-4334-AF88-A3290BCD9C0F',
						'refs' => array(),
						'rank' => Statement::RANK_NORMAL,
					)
				),
			),
		);
		$testCases['ignoreConflictItemMerge'] = array(
			array(
				'label' => array( 'en' => 'foo', 'pt' => 'ptfoo' ),
				'description' => array( 'en' => 'foo', 'pl' => 'pldesc' ),
				'aliases' => array( 'en' => array( 'foo', 'bar' ), 'de' => array( 'defoo', 'debar' ) ),
				'links' => array(
					'dewiki' => array( 'name' => 'foo', 'badges' => array() ),
					'plwiki' => array( 'name' => 'bar', 'badges' => array() ),
				),
				'claims' => array(
					array(
						'm' => array( 'novalue', 88 ),
						'q' => array( array( 'novalue', 88 ) ),
						'g' => 'Q111$D8404CDA-25E4-4334-AF88-A3290BCD9C0F',
						'refs' => array(),
						'rank' => Statement::RANK_NORMAL,
					)
				),
			),
			array(
				'label' => array( 'en' => 'toLabel' ),
				'description' => array( 'pl' => 'toLabel' ),
				'links' => array( 'plwiki' => array( 'name' => 'toLink', 'badges' => array() ) ),
			),
			array(
				'label' => array(),
				'description' => array( 'pl' => 'pldesc' ),
				'links' => array( 'plwiki' => array( 'name' => 'bar', 'badges' => array() ) ),
			),
			array(
				'label' => array( 'en' => 'toLabel', 'pt' => 'ptfoo' ),
				'description' => array( 'en' => 'foo', 'pl' => 'toLabel' ),
				'aliases' => array( 'en' => array( 'foo', 'bar' ), 'de' => array( 'defoo', 'debar' ) ),
				'links' => array(
					'dewiki' => array( 'name' => 'foo', 'badges' => array() ),
					'plwiki' => array( 'name' => 'toLink', 'badges' => array() ),
				),
				'claims' => array(
					array(
						'm' => array( 'novalue', 88 ),
						'q' => array( array( 'novalue', 88 ) ),
						'g' => 'Q111$D8404CDA-25E4-4334-AF88-A3290BCD9C0F',
						'refs' => array(),
						'rank' => Statement::RANK_NORMAL,
					)
				),
			),
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

	private function getEntityDeserializer() {
		$deserializerFactory = new DeserializerFactory(
			$this->getMock( 'Deserializers\Deserializer' ),
			$this->getMock( 'Wikibase\DataModel\Entity\EntityIdParser' )
		);
		return $deserializerFactory->newEntityDeserializer();
	}

}
