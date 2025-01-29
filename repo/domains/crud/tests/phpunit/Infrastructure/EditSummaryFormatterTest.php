<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure;

use Generator;
use MediaWikiLangTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Domains\Crud\Domain\Model\AliasesInLanguageEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\CreateItemEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\CreatePropertyEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\LabelEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\PatchItemEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\PatchPropertyEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\SitelinkEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\SitelinksEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\StatementEditSummary;
use Wikibase\Repo\Domains\Crud\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermsEditSummaryToFormattableSummaryConverter;
use Wikibase\Repo\Domains\Crud\Infrastructure\WholeEntityEditSummaryToFormattableSummaryConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\EditSummaryFormatter
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
	 * @dataProvider sitelinkEditSummaryProvider
	 * @dataProvider sitelinksEditSummaryProvider
	 * @dataProvider createItemEditSummaryProvider
	 * @dataProvider createPropertyEditSummaryProvider
	 */
	public function testFormat( EditSummary $editSummary, string $formattedSummary ): void {
		$editSummaryFormatter = new EditSummaryFormatter(
			WikibaseRepo::getSummaryFormatter(),
			new TermsEditSummaryToFormattableSummaryConverter(),
			new WholeEntityEditSummaryToFormattableSummaryConverter()
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

		yield 'remove label' => [
			LabelEditSummary::newRemoveSummary( 'remove user comment', new Term( 'en', 'LABEL-TEXT' ) ),
			'/* wbsetlabel-remove:1|en */ LABEL-TEXT, remove user comment',
		];

		yield 'remove label with no user comment' => [
			LabelEditSummary::newRemoveSummary( null, new Term( 'en', 'LABEL-TEXT' ) ),
			'/* wbsetlabel-remove:1|en */ LABEL-TEXT',
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
			StatementEditSummary::newAddSummary( 'user comment', NewStatement::noValueFor( 'P123' )->build() ),
			'/* wbsetclaim-create:1||1 */ [[Property:P123]]: no value, user comment',
		];

		yield 'remove statement' => [
			StatementEditSummary::newRemoveSummary( 'user comment 2', NewStatement::someValueFor( 'P321' )->build() ),
			'/* wbremoveclaims-remove:1| */ [[Property:P321]]: unknown value, user comment 2',
		];

		yield 'replace statement' => [
			StatementEditSummary::newReplaceSummary( 'user comment 3', NewStatement::noValueFor( 'P123' )->build() ),
			'/* wbsetclaim-update:1||1 */ [[Property:P123]]: no value, user comment 3',
		];

		yield 'patch statement' => [
			StatementEditSummary::newPatchSummary( 'user comment 4', NewStatement::noValueFor( 'P123' )->build() ),
			'/* wbsetclaim-update:1||1 */ [[Property:P123]]: no value, user comment 4',
		];

		yield 'add statement with no user comment' => [
			StatementEditSummary::newAddSummary( null, NewStatement::noValueFor( 'P123' )->build() ),
			'/* wbsetclaim-create:1||1 */ [[Property:P123]]: no value',
		];
	}

	public static function sitelinkEditSummaryProvider(): Generator {
		$userComment = 'user comment';
		$siteId = 'enwiki';
		$article = 'Potato';
		$anotherArticle = 'Old_Potato';
		$badges = [ new ItemId( 'Q123' ), new ItemId( 'Q345' ) ];
		$formattedBadgeItems = 'Q123, Q345';
		yield 'add sitelink without badges' => [
			SitelinkEditSummary::newAddSummary( $userComment, new SiteLink( $siteId, $article ) ),
			"/* wbsetsitelink-add:1|$siteId */ $article, $userComment",
		];
		yield 'add sitelink with badges' => [
			SitelinkEditSummary::newAddSummary( $userComment, new SiteLink( $siteId, $article, $badges ) ),
			"/* wbsetsitelink-add-both:2|$siteId */ $article, $formattedBadgeItems, $userComment",
		];
		yield 'replace sitelink without badges' => [
			SitelinkEditSummary::newReplaceSummary(
				$userComment,
				new SiteLink( $siteId, $article ),
				new SiteLink( $siteId, $anotherArticle )
			),
			"/* wbsetsitelink-set:1|$siteId */ $article, $userComment",
		];
		yield 'replace sitelink with badges' => [
			SitelinkEditSummary::newReplaceSummary(
				$userComment,
				new SiteLink( $siteId, $article, $badges ),
				new SiteLink( $siteId, $anotherArticle, [] )
			),
			"/* wbsetsitelink-set-both:2|$siteId */ $article, $formattedBadgeItems, $userComment",
		];
		yield 'replace badges of a sitelink only' => [
			SitelinkEditSummary::newReplaceSummary(
				$userComment,
				new SiteLink( $siteId, $article, $badges ),
				new SiteLink( $siteId, $article, [] )
			),
			"/* wbsetsitelink-set-badges:1|$siteId */ $formattedBadgeItems, $userComment",
		];
		yield 'remove sitelink' => [
			SitelinkEditSummary::newRemoveSummary( $userComment, new SiteLink( $siteId, $article ) ),
			"/* wbsetsitelink-remove:1|$siteId */ $article, $userComment",
		];
	}

	public static function sitelinksEditSummaryProvider(): Generator {
		yield 'patch sitelinks' => [
			SitelinksEditSummary::newPatchSummary( 'user comment' ),
			'/* wbeditentity-update:0| */ user comment',
		];
	}

	public static function createItemEditSummaryProvider(): Generator {
		yield 'create item' => [
			CreateItemEditSummary::newSummary( 'user comment' ),
			'/* wbeditentity-create-item:0| */ user comment',
		];
	}

	public static function createPropertyEditSummaryProvider(): Generator {
		yield 'create property' => [
			CreatePropertyEditSummary::newSummary( 'user comment' ),
			'/* wbeditentity-create-property:0| */ user comment',
		];
	}

	public function testGivenLabelsEditSummary_usesEditSummaryConverter(): void {
		$labelsEditSummary = $this->createStub( LabelsEditSummary::class );
		$editSummaryConverter = $this->createStub( WholeEntityEditSummaryToFormattableSummaryConverter::class );
		$converter = $this->createMock( TermsEditSummaryToFormattableSummaryConverter::class );
		$converter->expects( $this->once() )
			->method( 'convertLabelsEditSummary' )
			->with( $labelsEditSummary )
			->willReturn( new Summary( 'wbeditentity', 'update-languages-short', null, [ 'de, en' ] ) );
		$editSummaryFormatter = new EditSummaryFormatter( WikibaseRepo::getSummaryFormatter(), $converter, $editSummaryConverter );
		$this->assertSame(
			'/* wbeditentity-update-languages-short:0||de, en */',
			$editSummaryFormatter->format( $labelsEditSummary )
		);
	}

	public function testGivenDescriptionsEditSummary_usesEditSummaryConverter(): void {
		$descriptionsEditSummary = $this->createStub( DescriptionsEditSummary::class );
		$editSummaryConverter = $this->createStub( WholeEntityEditSummaryToFormattableSummaryConverter::class );
		$converter = $this->createMock( TermsEditSummaryToFormattableSummaryConverter::class );
		$converter->expects( $this->once() )
			->method( 'convertDescriptionsEditSummary' )
			->with( $descriptionsEditSummary )
			->willReturn( new Summary( 'wbeditentity', 'update-languages-short', null, [ 'de, en' ] ) );
		$editSummaryFormatter = new EditSummaryFormatter( WikibaseRepo::getSummaryFormatter(), $converter, $editSummaryConverter );
		$this->assertSame(
			'/* wbeditentity-update-languages-short:0||de, en */',
			$editSummaryFormatter->format( $descriptionsEditSummary )
		);
	}

	public function testPropertyEditSummary_usesEditSummaryConverter(): void {
		$propertyEditSummary = $this->createStub( PatchPropertyEditSummary::class );
		$converter = $this->createStub( TermsEditSummaryToFormattableSummaryConverter::class );

		$editSummaryConverter = $this->createMock( WholeEntityEditSummaryToFormattableSummaryConverter::class );
		$editSummaryConverter->expects( $this->once() )
			->method( 'newSummaryForPropertyPatch' )
			->with( $propertyEditSummary )
			->willReturn( new Summary( 'wbeditentity', 'update-languages-short', null, [ 'de, en' ] ) );

		$editSummaryFormatter = new EditSummaryFormatter( WikibaseRepo::getSummaryFormatter(), $converter, $editSummaryConverter );

		$this->assertSame( '/* wbeditentity-update-languages-short:0||de, en */', $editSummaryFormatter->format( $propertyEditSummary ) );
	}

	public function testItemPatchEditSummary_usesEditSummaryConverter(): void {
		$itemEditSummary = $this->createStub( PatchItemEditSummary::class );
		$itemEditSummary->method( 'getEditAction' )->willReturn( PatchItemEditSummary::PATCH_ACTION );

		$converter = $this->createStub( TermsEditSummaryToFormattableSummaryConverter::class );

		$editSummaryConverter = $this->createMock( WholeEntityEditSummaryToFormattableSummaryConverter::class );
		$editSummaryConverter->expects( $this->once() )
			->method( 'newSummaryForItemPatch' )
			->with( $itemEditSummary )
			->willReturn( new Summary( 'wbeditentity', 'update-languages-short', null, [ 'de, en' ] ) );

		$editSummaryFormatter = new EditSummaryFormatter( WikibaseRepo::getSummaryFormatter(), $converter, $editSummaryConverter );

		$this->assertSame( '/* wbeditentity-update-languages-short:0||de, en */', $editSummaryFormatter->format( $itemEditSummary ) );
	}

}
