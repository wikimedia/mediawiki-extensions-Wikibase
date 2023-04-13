<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use MediaWikiLangTestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
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
	 * @dataProvider labelEditSummaryProvider
	 * @dataProvider descriptionEditSummaryProvider
	 * @dataProvider statementEditSummaryProvider
	 */
	public function testFormat( EditSummary $editSummary, string $formattedSummary ): void {
		$editSummaryFormatter = new EditSummaryFormatter( WikibaseRepo::getSummaryFormatter() );
		$this->assertSame( $formattedSummary, $editSummaryFormatter->format( $editSummary ) );
	}

	public function labelEditSummaryProvider(): Generator {
		yield 'add label' => [
			LabelEditSummary::newAddSummary( 'add user comment', new Term( 'en', 'LABEL-TEXT' ) ),
			'/* wbsetlabel-add:1|en */ LABEL-TEXT, add user comment',
		];

		yield 'replace label' => [
			LabelEditSummary::newReplaceSummary( 'replace user comment', new Term( 'en', 'LABEL-TEXT' ) ),
			'/* wbsetlabel-set:1|en */ LABEL-TEXT, replace user comment',
		];

		yield 'replace label with no user comment' => [
			LabelEditSummary::newReplaceSummary( null, new Term( 'en', 'LABEL-TEXT' ) ),
			'/* wbsetlabel-set:1|en */ LABEL-TEXT',
		];
	}

	public function descriptionEditSummaryProvider(): Generator {
		yield 'replace description' => [
			new DescriptionEditSummary( new Term( 'en', 'DESCRIPTION-TEXT' ), 'replace user comment' ),
			'/* wbsetdescription-set:1|en */ DESCRIPTION-TEXT, replace user comment',
		];

		yield 'replace description with no user comment' => [
			new DescriptionEditSummary( new Term( 'en', 'DESCRIPTION-TEXT' ), null ),
			'/* wbsetdescription-set:1|en */ DESCRIPTION-TEXT',
		];
	}

	public function statementEditSummaryProvider(): Generator {
		// not using statements with values here because in order to format them, SummaryFormatter needs to look up the Property's data type
		// which means it needs to be persisted. This is unnecessary here, since we're testing the summary conversion and can assume that
		// the inner SummaryFormatter works fine.

		yield 'add statement' => [
			StatementEditSummary::newAddSummary(
				'user comment',
				NewStatement::noValueFor( 'P123' )->build()
			),
			'/* wbsetclaim-create:1||1 */ [[Property:P123]]: no value, user comment',
		];

		yield 'remove statement' => [
			StatementEditSummary::newRemoveSummary(
				'user comment 2',
				NewStatement::someValueFor( 'P321' )->build()
			),
			'/* wbremoveclaims-remove:1| */ [[Property:P321]]: unknown value, user comment 2',
		];

		yield 'replace statement' => [
			StatementEditSummary::newReplaceSummary(
				'user comment 3',
				NewStatement::noValueFor( 'P123' )->build()
			),
			'/* wbsetclaim-update:1||1 */ [[Property:P123]]: no value, user comment 3',
		];

		yield 'patch statement' => [
			StatementEditSummary::newPatchSummary(
				'user comment 4',
				NewStatement::noValueFor( 'P123' )->build()
			),
			'/* wbsetclaim-update:1||1 */ [[Property:P123]]: no value, user comment 4',
		];

		yield 'add statement with no user comment' => [
			StatementEditSummary::newAddSummary(
				null,
				NewStatement::noValueFor( 'P123' )->build()
			),
			'/* wbsetclaim-create:1||1 */ [[Property:P123]]: no value',
		];
	}

}
