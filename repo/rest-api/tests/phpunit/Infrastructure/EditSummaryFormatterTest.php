<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use MediaWikiLangTestCase;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Summary;
use Wikibase\Repo\RestApi\Domain\Model\AliasesInLanguageEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\RestApi\Infrastructure\TermsEditSummaryToFormattableSummaryConverter;
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
	 * @dataProvider aliasesInLanguageEditSummaryProvider
	 * @dataProvider statementEditSummaryProvider
	 */
	public function testFormat( EditSummary $editSummary, string $formattedSummary ): void {
		$editSummaryFormatter = new EditSummaryFormatter(
			WikibaseRepo::getSummaryFormatter(),
			new TermsEditSummaryToFormattableSummaryConverter()
		);
		$this->assertSame( $formattedSummary, $editSummaryFormatter->format( $editSummary ) );
	}

	public static function labelEditSummaryProvider(): Generator {
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

	public static function descriptionEditSummaryProvider(): Generator {
		yield 'add description' => [
			DescriptionEditSummary::newAddSummary( 'add user comment', new Term( 'en', 'DESCRIPTION-TEXT' ) ),
			'/* wbsetdescription-add:1|en */ DESCRIPTION-TEXT, add user comment',
		];

		yield 'add description with no user comment' => [
			DescriptionEditSummary::newAddSummary( null, new Term( 'en', 'DESCRIPTION-TEXT' ) ),
			'/* wbsetdescription-add:1|en */ DESCRIPTION-TEXT',
		];

		yield 'replace description' => [
			DescriptionEditSummary::newReplaceSummary( 'replace user comment', new Term( 'en', 'DESCRIPTION-TEXT' ) ),
			'/* wbsetdescription-set:1|en */ DESCRIPTION-TEXT, replace user comment',
		];

		yield 'replace description with no user comment' => [
			DescriptionEditSummary::newReplaceSummary( null, new Term( 'en', 'DESCRIPTION-TEXT' ) ),
			'/* wbsetdescription-set:1|en */ DESCRIPTION-TEXT',
		];

		yield 'remove description' => [
			DescriptionEditSummary::newRemoveSummary( 'remove user comment', new Term( 'en', 'DESCRIPTION-TEXT' ) ),
			'/* wbsetdescription-remove:1|en */ DESCRIPTION-TEXT, remove user comment',
		];

		yield 'remove description with no user comment' => [
			DescriptionEditSummary::newRemoveSummary( null, new Term( 'en', 'DESCRIPTION-TEXT' ) ),
			'/* wbsetdescription-remove:1|en */ DESCRIPTION-TEXT',
		];
	}

	public static function aliasesInLanguageEditSummaryProvider(): Generator {
		yield 'add en alias' => [
			AliasesInLanguageEditSummary::newAddSummary( null, new AliasGroup( 'en', [ 'spud' ] ) ),
			'/* wbsetaliases-add:1|en */ spud',
		];

		yield 'add de aliases with user comment' => [
			AliasesInLanguageEditSummary::newAddSummary(
				'added potato aliases',
				new AliasGroup( 'de', [ 'Erdapfel', 'Grundbirne' ] )
			),
			'/* wbsetaliases-add:2|de */ Erdapfel, Grundbirne, added potato aliases',
		];
	}

	public static function statementEditSummaryProvider(): Generator {
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

	public function testGivenLabelsEditSummary_usesEditSummaryConverter(): void {
		$labelsEditSummary = $this->createStub( LabelsEditSummary::class );
		$converter = $this->createMock( TermsEditSummaryToFormattableSummaryConverter::class );
		$converter->expects( $this->once() )
			->method( 'convertLabelsEditSummary' )
			->with( $labelsEditSummary )
			->willReturn(
				new Summary( 'wbeditentity', 'update-languages-short', null, [ 'de, en' ] )
			);
		$editSummaryFormatter = new EditSummaryFormatter(
			WikibaseRepo::getSummaryFormatter(),
			$converter
		);
		$this->assertSame(
			'/* wbeditentity-update-languages-short:0||de, en */',
			$editSummaryFormatter->format( $labelsEditSummary )
		);
	}

	public function testGivenDescriptionsEditSummary_usesEditSummaryConverter(): void {
		$descriptionsEditSummary = $this->createStub( DescriptionsEditSummary::class );
		$converter = $this->createMock( TermsEditSummaryToFormattableSummaryConverter::class );
		$converter->expects( $this->once() )
			->method( 'convertDescriptionsEditSummary' )
			->with( $descriptionsEditSummary )
			->willReturn(
				new Summary( 'wbeditentity', 'update-languages-short', null, [ 'de, en' ] )
			);
		$editSummaryFormatter = new EditSummaryFormatter(
			WikibaseRepo::getSummaryFormatter(),
			$converter
		);
		$this->assertSame(
			'/* wbeditentity-update-languages-short:0||de, en */',
			$editSummaryFormatter->format( $descriptionsEditSummary )
		);
	}

}
