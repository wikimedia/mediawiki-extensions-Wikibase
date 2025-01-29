<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermsEditSummaryToFormattableSummaryConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\TermsEditSummaryToFormattableSummaryConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermsEditSummaryToFormattableSummaryConverterTest extends TestCase {

	/**
	 * @dataProvider labelsEditSummaryProvider
	 */
	public function testConvertLabelsEditSummary( LabelsEditSummary $editSummary, Summary $formattedSummary ): void {
		$editSummaryFormatter = new TermsEditSummaryToFormattableSummaryConverter();
		$this->assertEquals( $formattedSummary, $editSummaryFormatter->convertLabelsEditSummary( $editSummary ) );
	}

	public static function labelsEditSummaryProvider(): Generator {
		return self::termsEditSummaryProvider( 'label', [ LabelsEditSummary::class, 'newPatchSummary' ] );
	}

	/**
	 * @dataProvider descriptionsEditSummaryProvider
	 */
	public function testConvertDescriptionsEditSummary( DescriptionsEditSummary $editSummary, Summary $formattedSummary ): void {
		$editSummaryFormatter = new TermsEditSummaryToFormattableSummaryConverter();
		$this->assertEquals( $formattedSummary, $editSummaryFormatter->convertDescriptionsEditSummary( $editSummary ) );
	}

	public static function descriptionsEditSummaryProvider(): Generator {
		return self::termsEditSummaryProvider( 'description', [ DescriptionsEditSummary::class, 'newPatchSummary' ] );
	}

	private static function termsEditSummaryProvider( string $term, callable $newSummaryMethod ): Generator {
		$dataSet = self::termsEditSummaryDataSet();
		foreach ( $dataSet as $msg => [ $comment, $original, $modified, $expectedSummary ] ) {
			yield sprintf( $msg, $term ) => [ $newSummaryMethod( $comment, $original, $modified ), $expectedSummary ];
		}
	}

	private static function termsEditSummaryDataSet(): Generator {
		yield 'replace many %1$ss' => [
			'patch user comment',
			new TermList(),
			new TermList( self::getLongTermsList() ),
			self::constructSummary(
				'update-languages',
				(string)count( self::getLongTermsList() ),
				'patch user comment'
			),
		];

		yield 'replace %1$ss with user comment' => [
			'patch user comment',
			new TermList(),
			new TermList( [ new Term( 'en', 'TERM-TEXT' ), new Term( 'de', 'TERM-TEXT-IN-GERMAN' ) ] ),
			self::constructSummary( 'update-languages-short', 'de, en', 'patch user comment' ),
		];

		yield 'add english %1$s' => [
			null,
			new TermList(),
			new TermList( [ new Term( 'en', 'ENGLISH-TERM' ) ] ),
			self::constructSummary( 'update-languages-short', 'en', null ),
		];

		yield 'add a german %1$s with an existing english %1$s' => [
			null,
			new TermList( [ new Term( 'en', 'ENGLISH-TERM' ) ] ),
			new TermList( [ new Term( 'de', 'GERMAN-TERM' ), new Term( 'en', 'ENGLISH-TERM' ) ] ),
			self::constructSummary( 'update-languages-short', 'de', null ),
		];

		yield 'modify english %1$s' => [
			null,
			new TermList( [ new Term( 'en', 'ENGLISH-TERM' ) ] ),
			new TermList( [ new Term( 'en', 'MODIFIED-ENGLISH-TERM' ) ] ),
			self::constructSummary( 'update-languages-short', 'en', null ),
		];

		yield 'delete english %1$s' => [
			null,
			new TermList( [ new Term( 'en', 'ENGLISH-TERM' ) ] ),
			new TermList(),
			self::constructSummary( 'update-languages-short', 'en', null ),
		];
	}

	private static function getLongTermsList(): array {
		$longLabelsList = [];
		$languages = WikibaseRepo::getTermsLanguages()->getLanguages();

		foreach ( $languages as $language ) {
			$longLabelsList[] = new Term( $language, "new term in {$language}" );
		}
		return $longLabelsList;
	}

	private static function constructSummary( string $actionName, string $autoCommentArgs, ?string $userComment ): Summary {
		$summary = new Summary( 'wbeditentity', $actionName, null, [ $autoCommentArgs ] );
		$summary->setUserSummary( $userComment );
		return $summary;
	}

}
