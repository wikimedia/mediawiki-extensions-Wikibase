<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use MediaWikiLangTestCase;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EditSummaryFormatterTest extends MediaWikiLangTestCase {

	/**
	 * @dataProvider editSummaryProvider
	 */
	public function testFormat( EditSummary $editSummary, string $formattedSummary ): void {
		$this->assertSame(
			$formattedSummary,
			( new EditSummaryFormatter( WikibaseRepo::getSummaryFormatter() ) )
				->format( $editSummary )
		);
	}

	public function editSummaryProvider(): Generator {
		// not using statements with values here because in order to format them, SummaryFormatter needs to look up the Property's data type
		// which means it needs to be persisted. This is unnecessary here, since we're testing the summary conversion and can assume that
		// the inner SummaryFormatter works fine.

		yield 'add' => [
			StatementEditSummary::newAddSummary(
				'user comment',
				NewStatement::noValueFor( 'P123' )->build()
			),
			'/* wbsetclaim-create:1||1 */ [[Property:P123]]: no value, user comment',
		];

		yield 'remove' => [
			StatementEditSummary::newRemoveSummary(
				'user comment 2',
				NewStatement::someValueFor( 'P321' )->build()
			),
			'/* wbremoveclaims-remove:1| */ [[Property:P321]]: unknown value, user comment 2',
		];

		yield 'replace' => [
			StatementEditSummary::newReplaceSummary(
				'user comment 3',
				NewStatement::noValueFor( 'P123' )->build()
			),
			'/* wbsetclaim-update:1||1 */ [[Property:P123]]: no value, user comment 3',
		];

		yield 'patch' => [
			StatementEditSummary::newPatchSummary(
				'user comment 4',
				NewStatement::noValueFor( 'P123' )->build()
			),
			'/* wbsetclaim-update:1||1 */ [[Property:P123]]: no value, user comment 4',
		];

		yield 'no user comment' => [
			StatementEditSummary::newAddSummary(
				null,
				NewStatement::noValueFor( 'P123' )->build()
			),
			'/* wbsetclaim-create:1||1 */ [[Property:P123]]: no value',
		];
	}

}
