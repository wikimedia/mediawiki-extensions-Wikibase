<?php

namespace Wikibase\Lib\Tests\Changes;

use Diff\DiffOp\DiffOpAdd;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;
use Wikibase\Lib\Changes\EntityDiffChangedAspectsFactory;

/**
 * @covers \Wikibase\Lib\Changes\EntityDiffChangedAspectsFactory
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityDiffChangedAspectsFactoryTest extends \PHPUnit\Framework\TestCase {

	public function provideNewFromEntityDiff() {
		$emptyDiff = [
			'arrayFormatVersion' => EntityDiffChangedAspects::ARRAYFORMATVERSION,
			'labelChanges' => [],
			'descriptionChanges' => [],
			'statementChanges' => [],
			'siteLinkChanges' => [],
			'otherChanges' => false,
		];

		$labelDiff = $emptyDiff;
		$labelDiff['labelChanges'] = [ 'de' ];

		$descriptionDiff = $emptyDiff;
		$descriptionDiff['descriptionChanges'] = [ 'ru' ];

		$statementP1Diff = $emptyDiff;
		$statementP1Diff['statementChanges'] = [ 'P1' ];

		$statementP2Diff = $emptyDiff;
		$statementP2Diff['statementChanges'] = [ 'P2' ];

		$statementP1P2Diff = $emptyDiff;
		$statementP1P2Diff['statementChanges'] = [ 'P1', 'P2' ];

		$siteLinkDiff = $emptyDiff;
		$siteLinkDiff['siteLinkChanges'] = [ 'enwiki' => [ null, 'PHP', false ] ];

		$siteLinkWithBadgeDiff = $emptyDiff;
		$siteLinkWithBadgeDiff['siteLinkChanges'] = [ 'enwiki' => [ null, 'PHP', true ] ];

		$siteLinkBadgeOnlyDiff = $emptyDiff;
		$siteLinkBadgeOnlyDiff['siteLinkChanges'] = [ 'enwiki' => [ null, null, true ] ];

		$berlinEmptyDiff = [
			'arrayFormatVersion' => EntityDiffChangedAspects::ARRAYFORMATVERSION,
			'labelChanges' => [ 'de', 'ru' ],
			'descriptionChanges' => [ 'de', 'es' ],
			'statementChanges' => [ 'P2' ],
			'siteLinkChanges' => [
				'dewiki' => [ null, 'Berlin', true ],
				'enwiki' => [ null, 'Berlin', false ],
			],
			'otherChanges' => false,
		];

		$parisEmptyDiff = [
			'arrayFormatVersion' => EntityDiffChangedAspects::ARRAYFORMATVERSION,
			'labelChanges' => [ 'fr', 'ru' ],
			'descriptionChanges' => [ 'de', 'es', 'pl' ],
			'statementChanges' => [ 'P2' ],
			'siteLinkChanges' => [
				'dewiki' => [ null, 'Paris', true ],
				'enwiki' => [ null, 'Paris', false ],
				'ruwiki' => [ null, 'Paris', false ],
			],
			'otherChanges' => false,
		];

		$berlinParisDiff = [
			'arrayFormatVersion' => EntityDiffChangedAspects::ARRAYFORMATVERSION,
			'labelChanges' => [ 'de', 'fr', 'ru' ],
			'descriptionChanges' => [ 'pl' ],
			'statementChanges' => [ 'P2' ],
			'siteLinkChanges' => [
				'dewiki' => [ 'Berlin', 'Paris', false ],
				'enwiki' => [ 'Berlin', 'Paris', false ],
				'ruwiki' => [ null, 'Paris', false ],
			],
			'otherChanges' => false,
		];

		$q2 = new ItemId( 'Q2' );
		$p1 = new NumericPropertyId( 'P1' );
		$p2 = new NumericPropertyId( 'P2' );

		$noValueP1Statement = new Statement( new PropertyNoValueSnak( $p1 ) );
		$noValueP1Statements = new StatementList( $noValueP1Statement );

		$noValueP2Statement = new Statement( new PropertyNoValueSnak( $p2 ) );
		$noValueP2Statements = new StatementList( $noValueP2Statement );

		$someValueStatement = new Statement( new PropertySomeValueSnak( $p2 ) );
		$someValueStatements = new StatementList( $someValueStatement );

		$emptyItem = new Item( $q2, null, null, null );
		$emptyProperty = new Property( $p2, null, 'hey', null );

		$labelItem = $emptyItem->copy();
		$labelItem->setLabel( 'de', 'de label' );

		$descriptionItem = $emptyItem->copy();
		$descriptionItem->setDescription( 'ru', 'ru desc' );

		$noValueP1StatementItem = $emptyItem->copy();
		$noValueP1StatementItem->setStatements( $noValueP1Statements );

		$noValueP2StatementItem = $emptyItem->copy();
		$noValueP2StatementItem->setStatements( $noValueP2Statements );

		$someValueStatementItem = $emptyItem->copy();
		$someValueStatementItem->setStatements( $someValueStatements );

		$siteLinkItem = $emptyItem->copy();
		$siteLinkItem->addSiteLink( new SiteLink( 'enwiki', 'PHP' ) );

		$siteLinkBadgeItem = $emptyItem->copy();
		$siteLinkBadgeItem->addSiteLink( new SiteLink( 'enwiki', 'PHP', [ $q2 ] ) );

		$berlinItem = $emptyItem->copy();
		$berlinItem->addSiteLink( new SiteLink( 'enwiki', 'Berlin' ) );
		$berlinItem->addSiteLink( new SiteLink( 'dewiki', 'Berlin', [ $q2 ] ) );
		$berlinItem->setLabel( 'de', 'Berlin' );
		$berlinItem->setLabel( 'ru', 'Берлин' );
		$berlinItem->setDescription( 'de', 'abc' );
		$berlinItem->setDescription( 'es', 'def' );
		$berlinItem->setStatements( $someValueStatements );

		$parisItem = $emptyItem->copy();
		$parisItem->addSiteLink( new SiteLink( 'enwiki', 'Paris' ) );
		$parisItem->addSiteLink( new SiteLink( 'dewiki', 'Paris', [ $q2 ] ) );
		$parisItem->addSiteLink( new SiteLink( 'ruwiki', 'Paris' ) );
		$parisItem->setLabel( 'fr', 'Paris' );
		$parisItem->setLabel( 'ru', 'ru label' );
		$parisItem->setDescription( 'de', 'abc' );
		$parisItem->setDescription( 'es', 'def' );
		$parisItem->setDescription( 'pl', 'xyz' );
		$parisItem->setStatements( $noValueP2Statements );

		$noValueP1Property = $emptyProperty->copy();
		$noValueP1Property->setStatements( $noValueP1Statements );

		$cases = [
			'$emptyItem === $emptyItem' => [
				$emptyDiff,
				$emptyItem,
				$emptyItem,
			],
			'$emptyProperty === $emptyProperty' => [
				$emptyDiff,
				$emptyProperty,
				$emptyProperty,
			],
			'$labelItem === $labelItem' => [
				$emptyDiff,
				$labelItem,
				$labelItem,
			],
			'$descriptionItem === $descriptionItem' => [
				$emptyDiff,
				$descriptionItem,
				$descriptionItem,
			],
			'$noValueP1StatementItem === $noValueP1StatementItem' => [
				$emptyDiff,
				$noValueP1StatementItem,
				$noValueP1StatementItem,
			],
			'$noValueP2StatementItem === $noValueP2StatementItem' => [
				$emptyDiff,
				$noValueP2StatementItem,
				$noValueP2StatementItem,
			],
			'$someValueStatementItem === $someValueStatementItem' => [
				$emptyDiff,
				$someValueStatementItem,
				$someValueStatementItem,
			],
			'$siteLinkItem === $siteLinkItem' => [
				$emptyDiff,
				$siteLinkItem,
				$siteLinkItem,
			],
			'$siteLinkBadgeItem === $siteLinkBadgeItem' => [
				$emptyDiff,
				$siteLinkBadgeItem,
				$siteLinkBadgeItem,
			],
			'$berlinItem === $berlinItem' => [
				$emptyDiff,
				$berlinItem,
				$berlinItem,
			],
			'$parisItem === $parisItem' => [
				$emptyDiff,
				$parisItem,
				$parisItem,
			],
			'$noValueP1Property === $noValueP1Property' => [
				$emptyDiff,
				$noValueP1Property,
				$noValueP1Property,
			],
			'label change' => [
				$labelDiff,
				$emptyItem,
				$labelItem,
			],
			'description changes' => [
				$descriptionDiff,
				$emptyItem,
				$descriptionItem,
			],
			'item statement change (no value)' => [
				$statementP1Diff,
				$emptyItem,
				$noValueP1StatementItem,
			],
			'property statement change (no value)' => [
				$statementP1Diff,
				$emptyProperty,
				$noValueP1Property,
			],
			'statement change (some value)' => [
				$statementP2Diff,
				$emptyItem,
				$someValueStatementItem,
			],
			'statement change (other property id + some value <> no value)' => [
				$statementP1P2Diff,
				$someValueStatementItem,
				$noValueP1StatementItem,
			],
			'statement change (some property id + some value <> no value)' => [
				$statementP2Diff,
				$someValueStatementItem,
				$noValueP2StatementItem,
			],
			'sitelink changes' => [
				$siteLinkDiff,
				$emptyItem,
				$siteLinkItem,
			],
			'sitelink change with badge' => [
				$siteLinkWithBadgeDiff,
				$emptyItem,
				$siteLinkBadgeItem,
			],
			'sitelink badge only changes' => [
				$siteLinkBadgeOnlyDiff,
				$siteLinkItem,
				$siteLinkBadgeItem,
			],
			'berlin item <> empty item' => [
				$berlinEmptyDiff,
				$emptyItem,
				$berlinItem,
			],
			'paris item <> empty item' => [
				$parisEmptyDiff,
				$emptyItem,
				$parisItem,
			],
			'paris item <> berlin item' => [
				$berlinParisDiff,
				$berlinItem,
				$parisItem,
			],
		];

		// All cases should result in the same aspect diff if the old and new entity are exchanged.
		$reverseTests = [];
		foreach ( $cases as $testDescription => $case ) {
			$reverseTests[$testDescription . ' (reversed)'] = [
				$this->reverseDiff( $case[0] ),
				$case[2],
				$case[1],
			];
		}

		return array_merge( $cases, $reverseTests );
	}

	private function reverseDiff( array $diffs ) {
		$newDiff = [];
		foreach ( $diffs as $diffType => $diff ) {
			if ( $diffType === 'siteLinkChanges' ) {
				$newDiff[$diffType] = $this->reverseSiteLinkDiff( $diff );
				continue;
			}

			$newDiff[$diffType] = $diff;
		}

		return $newDiff;
	}

	private function reverseSiteLinkDiff( $diff ) {
		$newSiteLinkDiff = [];
		foreach ( $diff as $site => $siteDiff ) {
			$newSiteLinkDiff[$site] = [ $siteDiff[1], $siteDiff[0], $siteDiff[2] ];
		}
		return $newSiteLinkDiff;
	}

	/**
	 * @dataProvider provideNewFromEntityDiff
	 */
	public function testNewFromEntityDiff(
		array $expectedDiffArray,
		EntityDocument $oldEntity,
		EntityDocument $newEntity
	) {
		$entityDiffer = new EntityDiffer();
		$entityDiff = $entityDiffer->diffEntities( $oldEntity, $newEntity );

		$entityDiffChangedAspects = ( new EntityDiffChangedAspectsFactory() )->newFromEntityDiff( $entityDiff );
		$actual = $entityDiffChangedAspects->toArray();

		$this->sortSubArrays( $actual );
		$this->sortSubArrays( $expectedDiffArray );

		$this->assertEquals( $expectedDiffArray, $actual );
	}

	public function testNewFromEntityDiff_otherChanges() {
		$entityDiff = new EntityDiff();
		// Add some unknown change
		$entityDiff->addOperations( [ new DiffOpAdd( 1 ) ] );

		$entityDiffChangedAspects = ( new EntityDiffChangedAspectsFactory() )->newFromEntityDiff( $entityDiff );
		$actual = $entityDiffChangedAspects->toArray();

		$expectedDiff = [
			'arrayFormatVersion' => EntityDiffChangedAspects::ARRAYFORMATVERSION,
			'labelChanges' => [],
			'descriptionChanges' => [],
			'statementChanges' => [],
			'siteLinkChanges' => [],
			'otherChanges' => true,
		];

		$this->assertEquals( $expectedDiff, $actual );
	}

	/**
	 * Sort all sub-arrays (but leave the array itself alone).
	 *
	 * @param array &$arr
	 */
	private function sortSubArrays( array &$arr ) {
		foreach ( $arr as &$subArr ) {
			if ( is_array( $subArr ) ) {
				sort( $subArr );
			}
		}
	}

}
