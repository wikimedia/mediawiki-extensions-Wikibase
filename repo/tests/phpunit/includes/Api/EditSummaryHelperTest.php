<?php

namespace Wikibase\Repo\Tests\Api;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\EditSummaryHelper;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpResultStub;
use Wikibase\Repo\Tests\ChangeOp\LanguageBoundChangeOpResultStub;

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
		$entityId = new ItemId( 'Q123' );

		$fiftyOneChangeOps = [];
		for ( $i = 1; $i <= 51; $i++ ) {
			$fiftyOneChangeOps[] = new LanguageBoundChangeOpResultStub( $entityId, true, "en-x-$i" );
		}

		return [
			'no terms changed' => [
				'changeOpResult' => new ChangeOpsResult( $entityId, [] ),
				'expectedAction' => 'update',
				'expectedAutoCommentArgs' => [],
			],
			'only terms changed in less than 50 languages' => [
				'changeOpResult' => new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
				'expectedAction' => 'update-languages-short',
				'expectedAutoCommentArgs' => [ [ 'en' ] ],
			],
			'terms in less than 50 languages and other parts changed' => [
				'changeOpResult' => new ChangeOpsResult( $entityId, [
					new ChangeOpsResult( $entityId, [
						new LanguageBoundChangeOpResultStub( $entityId, true, 'fr' ),
						new LanguageBoundChangeOpResultStub( $entityId, false, 'de' ),
					] ),
					new LanguageBoundChangeOpResultStub( $entityId, true, 'fr' ),
					new ChangeOpResultStub( $entityId, true ),
				] ),
				'expectedAction' => 'update-languages-and-other-short',
				'expectedAutoCommentArgs' => [ [ 'fr' ] ],
			],
			'terms in more than 50 languages changed' => [
				'changeOpResult' => new ChangeOpsResult( $entityId, [
					...$fiftyOneChangeOps,
					new ChangeOpResultStub( $entityId, false ),
				] ),
				'expectedAction' => 'update-languages',
				'expectedAutoCommentArgs' => [ 51 ],
			],
			'terms in more than 50 languages and other parts changed' => [
				'changeOpResult' => new ChangeOpsResult( $entityId, [
					...$fiftyOneChangeOps,
					new ChangeOpResultStub( $entityId, true ),
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
		ChangeOpResult $changeOpResult,
		$expectedAction,
		$expectedAutoCommentArgs
	) {
		$summary = new Summary();

		$editSummaryHelper = new EditSummaryHelper();
		$editSummaryHelper->prepareEditSummary( $summary, $changeOpResult );

		$this->assertEquals( $expectedAction, $summary->getMessageKey() );
		$this->assertEquals( $expectedAutoCommentArgs, $summary->getCommentArgs() );
	}
}
