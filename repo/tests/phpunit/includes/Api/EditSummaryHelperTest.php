<?php

namespace Wikibase\Repo\Tests\Api;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Tests\NewStatement;
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
			'no terms changed' => [
				'entityDiff' => new EntityDiff( [ 'claim' => $statementsDiff ] ),
				'expectedAction' => 'wbeditentity-update',
				'expectedAutoCommentArgs' => [],
			],
			'only terms changed in less than 50 languages' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( [ 'en' => new DiffOpChange( 'old en label', 'new en label' ) ], true ),
				] ),
				'expectedAction' => 'wbeditentity-update-languages-short',
				'expectedAutoCommentArgs' => [ [ 'en' ] ],
			],
			'terms in less than 50 languages and other parts changed' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( [ 'fr' => new DiffOpChange( 'old fr label', 'new fr label' ) ], true ),
					'claim' => $statementsDiff,
				] ),
				'expectedAction' => 'wbeditentity-update-languages-and-other-short',
				'expectedAutoCommentArgs' => [ [ 'fr' ] ],
			],
			'terms in more than 50 languages changed' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( $fiftyOneDiffs, true ),
				] ),
				'expectedAction' => 'wbeditentity-update-languages',
				'expectedAutoCommentArgs' => [ 51 ],
			],
			'terms in more than 50 languages and other parts changed' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( $fiftyOneDiffs, true ),
					'claim' => $statementsDiff,
				] ),
				'expectedAction' => 'wbeditentity-update-languages-and-other',
				'expectedAutoCommentArgs' => [ 51 ],
			],
		];
	}

	/**
	 * @dataProvider provideEntityDiffsForGetEditSummary
	 */
	public function testGetEditSummary(
		EntityDiff $entityDiff,
		string $expectedAction,
		array $expectedAutoCommentArgs
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

		$this->assertEquals( $expectedAction, $summary->getMessageKey() );
		$this->assertEquals( $expectedAutoCommentArgs, $summary->getCommentArgs() );
		$this->assertSame( 'user summary', $summary->getUserSummary() );
	}
}
