<?php

namespace Wikibase\Repo\Tests\Api;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Services\Diff\EntityDiff;
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
	public static function provideChangeOpResultsForPrepareEditSummary() {
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
				'expectedAction' => 'update',
				'expectedAutoCommentArgs' => [],
			],
			'only terms changed in less than 50 languages' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( [ 'en' => new DiffOpChange( 'old en label', 'new en label' ) ], true ),
				] ),
				'expectedAction' => 'update-languages-short',
				'expectedAutoCommentArgs' => [ [ 'en' ] ],
			],
			'terms in less than 50 languages and other parts changed' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( [ 'fr' => new DiffOpChange( 'old fr label', 'new fr label' ) ], true ),
					'claim' => $statementsDiff,
				] ),
				'expectedAction' => 'update-languages-and-other-short',
				'expectedAutoCommentArgs' => [ [ 'fr' ] ],
			],
			'terms in more than 50 languages changed' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( $fiftyOneDiffs, true ),
				] ),
				'expectedAction' => 'update-languages',
				'expectedAutoCommentArgs' => [ 51 ],
			],
			'terms in more than 50 languages and other parts changed' => [
				'entityDiff' => new EntityDiff( [
					'label' => new Diff( $fiftyOneDiffs, true ),
					'claim' => $statementsDiff,
				] ),
				'expectedAction' => 'update-languages-and-other',
				'expectedAutoCommentArgs' => [ 51 ],
			],
		];
	}

	/**
	 * @dataProvider provideChangeOpResultsForPrepareEditSummary
	 */
	public function testPrepareEditSummary(
		EntityDiff $entityDiff,
		$expectedAction,
		$expectedAutoCommentArgs
	) {
		$summary = new Summary();

		$editSummaryHelper = new EditSummaryHelper();
		$editSummaryHelper->prepareEditSummary( $summary, $entityDiff );

		$this->assertEquals( $expectedAction, $summary->getMessageKey() );
		$this->assertEquals( $expectedAutoCommentArgs, $summary->getCommentArgs() );
	}
}
