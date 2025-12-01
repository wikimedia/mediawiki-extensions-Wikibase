<?php

namespace Wikibase\Repo\Tests\Api;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\EditSummaryHelper;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @covers \Wikibase\Repo\Api\EditSummaryHelper
 * @license GPL-2.0-or-later
 */
class EditSummaryHelperTest extends \PHPUnit\Framework\TestCase {
	public static function provideEntityDiffsForGetEditSummary() {
		$statementId = 'Q123$00000000-0000-0000-0000-000000000000';
		$oldStatement = NewStatement::noValueFor( 'P1' )->build();
		$newStatement = NewStatement::someValueFor( 'P1' )->build();
		$statementsDiff = new Diff( [
			$statementId => new DiffOpChange( $oldStatement, $newStatement ),
		], true );

		$fiftyOneDiffs = [];
		for ( $i = 1; $i <= 51; $i++ ) {
			$fiftyOneDiffs["en-x-$i"] = new DiffOpChange( "old en-x-$i label", "new en-x-$i label" );
		}

		return [
			'only terms changed in less than 50 languages' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( [ 'en' => new DiffOpChange( 'old en label', 'new en label' ) ], true ),
				] ),
				'expected' => new Summary(
					'wbeditentity',
					'update-languages-short',
					commentArgs: [ [ 'en' ] ],
				),
			],
			'terms in less than 50 languages and other parts changed' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( [ 'fr' => new DiffOpChange( 'old fr label', 'new fr label' ) ], true ),
					'claim' => $statementsDiff,
				] ),
				'expected' => new Summary(
					'wbeditentity',
					'update-languages-and-other-short',
					commentArgs: [ [ 'fr' ] ],
				),
			],
			'terms in more than 50 languages changed' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( $fiftyOneDiffs, true ),
				] ),
				'expected' => new Summary(
					'wbeditentity',
					'update-languages',
					commentArgs: [ 51 ],
				),
			],
			'terms in more than 50 languages and other parts changed' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( $fiftyOneDiffs, true ),
					'claim' => $statementsDiff,
				] ),
				'expected' => new Summary(
					'wbeditentity',
					'update-languages-and-other',
					commentArgs: [ 51 ],
				),
			],
			'single statement changed' => [
				'entityDiff' => new EntityDiff( [ 'claim' => $statementsDiff ] ),
				'expected' => new Summary(
					'wbsetclaim',
					'update',
					commentArgs: [ 1 ], // one statement changed
					summaryArgs: [ [ 'P1' => $newStatement->getMainSnak() ] ],
				),
			],
			'single statement added' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					$statementId => new DiffOpAdd( $newStatement ),
				], true ) ] ),
				'expected' => new Summary(
					'wbsetclaim',
					'create',
					commentArgs:  [ 1 ], // one statement added
					summaryArgs: [ [ 'P1' => $newStatement->getMainSnak() ] ],
				),
			],
			'single statement removed' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					$statementId => new DiffOpRemove( $oldStatement ),
				], true ) ] ),
				'expected' => new Summary(
					'wbremoveclaims',
					'remove',
					summaryArgs: [ [ 'P1' => $oldStatement->getMainSnak() ] ],
				),
			],
			'multiple statements for the same property ID added' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					'Q123$00000000-0000-0000-0000-000000000000' => new DiffOpAdd(
						$newStatement,
					),
					'Q123$00000000-0000-0000-0000-000000000001' => new DiffOpAdd(
						$newStatement,
					),
				], true ) ] ),
				'expected' => new Summary(
					'wbeditentity',
					'statements-single-property-add',
					commentArgs: [ 2 ], // two statements changed
					summaryArgs: [ $oldStatement->getPropertyId() ],
				),
			],
			'multiple statements for the same property ID removed' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					'Q123$00000000-0000-0000-0000-000000000000' => new DiffOpRemove(
						$oldStatement,
					),
					'Q123$00000000-0000-0000-0000-000000000001' => new DiffOpRemove(
						$oldStatement,
					),
				], true ) ] ),
				'expected' => new Summary(
					'wbeditentity',
					'statements-single-property-remove',
					commentArgs: [ 2 ], // two statements changed
					summaryArgs: [ $oldStatement->getPropertyId() ],
				),
			],
			'multiple statements for the same property ID changed' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					'Q123$00000000-0000-0000-0000-000000000000' => new DiffOpChange(
						$oldStatement,
						$newStatement,
					),
					'Q123$00000000-0000-0000-0000-000000000001' => new DiffOpChange(
						$oldStatement,
						$newStatement,
					),
				], true ) ] ),
				'expected' => new Summary(
					'wbeditentity',
					'statements-single-property-update',
					commentArgs: [ 2 ], // two statements changed
					summaryArgs: [ $oldStatement->getPropertyId() ],
				),
			],
			'multiple statements for the same property ID edited (mixed added + removed)' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					'Q123$00000000-0000-0000-0000-000000000000' => new DiffOpAdd(
						$newStatement,
					),
					'Q123$00000000-0000-0000-0000-000000000001' => new DiffOpRemove(
						$oldStatement,
					),
				], true ) ] ),
				'expected' => new Summary(
					'wbeditentity',
					'statements-single-property-update',
					commentArgs: [ 2 ], // two statements changed
					summaryArgs: [ $oldStatement->getPropertyId() ],
				),
			],
			'multiple statements for different property IDs added' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					'Q123$00000000-0000-0000-0000-000000000000' => new DiffOpAdd(
						$newStatement,
					),
					'Q123$00000000-0000-0000-0000-000000000001' => new DiffOpAdd(
						NewStatement::someValueFor( 'P2' )->build(),
					),
					'Q123$00000000-0000-0000-0000-000000000002' => new DiffOpAdd(
						NewStatement::noValueFor( 'P2' )->build(),
					),
				], true ) ] ),
				'expected' => new Summary(
					'wbeditentity',
					'statements-multiple-properties-add',
					commentArgs: [ 2 ], // two properties affected (across three statements)
				),
			],
			'multiple statements for different property IDs removed' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					'Q123$00000000-0000-0000-0000-000000000000' => new DiffOpRemove(
						$oldStatement,
					),
					'Q123$00000000-0000-0000-0000-000000000001' => new DiffOpRemove(
						NewStatement::noValueFor( 'P2' )->build(),
					),
					'Q123$00000000-0000-0000-0000-000000000002' => new DiffOpRemove(
						NewStatement::someValueFor( 'P2' )->build(),
					),
				], true ) ] ),
				'expected' => new Summary(
					'wbeditentity',
					'statements-multiple-properties-remove',
					commentArgs: [ 2 ], // two properties affected (across three statements)
				),
			],
			'multiple statements for different property IDs changed' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					'Q123$00000000-0000-0000-0000-000000000000' => new DiffOpChange(
						$oldStatement,
						$newStatement,
					),
					'Q123$00000000-0000-0000-0000-000000000001' => new DiffOpChange(
						NewStatement::noValueFor( 'P2' )->build(),
						NewStatement::someValueFor( 'P2' )->build(),
					),
					'Q123$00000000-0000-0000-0000-000000000002' => new DiffOpChange(
						NewStatement::someValueFor( 'P2' )->build(),
						NewStatement::noValueFor( 'P2' )->build(),
					),
				], true ) ] ),
				'expected' => new Summary(
					'wbeditentity',
					'statements-multiple-properties-update',
					commentArgs: [ 2 ], // two properties affected (across three statements)
				),
			],
			'multiple statements for different property IDs edited (mixed added + removed)' => [
				'entityDiff' => new EntityDiff( [ 'claim' => new Diff( [
					'Q123$00000000-0000-0000-0000-000000000000' => new DiffOpAdd(
						$newStatement,
					),
					'Q123$00000000-0000-0000-0000-000000000001' => new DiffOpAdd(
						NewStatement::someValueFor( 'P2' )->build(),
					),
					'Q123$00000000-0000-0000-0000-000000000002' => new DiffOpRemove(
						NewStatement::someValueFor( 'P2' )->build(),
					),
				], true ) ] ),
				'expected' => new Summary(
					'wbeditentity',
					'statements-multiple-properties-update',
					commentArgs: [ 2 ], // two properties affected (across three statements)
				),
			],
		];
	}

	/**
	 * @dataProvider provideEntityDiffsForGetEditSummary
	 */
	public function testGetEditSummary(
		EntityDiff $entityDiff,
		Summary $expected,
	) {
		$oldEntity = new Item();
		$newEntity = new Item();
		$entityDiffer = $this->createMock( EntityDiffer::class );
		$entityDiffer->expects( $this->once() )
			->method( 'diffEntities' )
			->with( $oldEntity, $newEntity )
			->willReturn( $entityDiff );
		$editSummaryHelper = new EditSummaryHelper( $entityDiffer );

		$preparedParameters = [
			'id' => 'Q1',
			'clear' => false,
			'summary' => 'user summary',
		];
		$summary = $editSummaryHelper->getEditSummary( $preparedParameters, $oldEntity, $newEntity );

		$expected->setUserSummary( 'user summary' );
		$this->assertEquals( $expected, $summary );
	}
}
