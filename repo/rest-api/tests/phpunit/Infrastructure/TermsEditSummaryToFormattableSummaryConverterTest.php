<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Summary;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Infrastructure\TermsEditSummaryToFormattableSummaryConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\TermsEditSummaryToFormattableSummaryConverter
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

	public function labelsEditSummaryProvider(): Generator {
		return $this->termsEditSummaryProvider( 'label', [ LabelsEditSummary::class, 'newPatchSummary' ] );
	}

	/**
	 * @dataProvider descriptionsEditSummaryProvider
	 */
	public function testConvertDescriptionsEditSummary( DescriptionsEditSummary $editSummary, Summary $formattedSummary ): void {
		$editSummaryFormatter = new TermsEditSummaryToFormattableSummaryConverter();
		$this->assertEquals( $formattedSummary, $editSummaryFormatter->convertDescriptionsEditSummary( $editSummary ) );
	}

	public function descriptionsEditSummaryProvider(): Generator {
		return $this->termsEditSummaryProvider( 'description', [ DescriptionsEditSummary::class, 'newPatchSummary' ] );
	}

	private function termsEditSummaryProvider( string $term, callable $newSummaryMethod ): Generator {
		$dataSet = $this->termsEditSummaryDataSet();
		foreach ( $dataSet as $msg => [ $comment, $original, $modified, $expectedSummary ] ) {
			yield sprintf( $msg, $term ) => [ $newSummaryMethod( $comment, $original, $modified ), $expectedSummary ];
		}
	}

	private function termsEditSummaryDataSet(): Generator {
		yield 'replace many %1$ss' => [
			'patch user comment',
			new TermList(),
			new TermList( $this->getLongTermsList() ),
			$this->constructSummary(
				'update-languages',
				(string)count( $this->getLongTermsList() ),
				'patch user comment'
			),
		];

		yield 'replace %1$ss with user comment' => [
			'patch user comment',
			new TermList(),
			new TermList( [ new Term( 'en', 'TERM-TEXT' ), new Term( 'de', 'TERM-TEXT-IN-GERMAN' ) ] ),
			$this->constructSummary( 'update-languages-short', 'de, en', 'patch user comment' ),
		];

		yield 'add english %1$s' => [
			null,
			new TermList(),
			new TermList( [ new Term( 'en', 'ENGLISH-TERM' ) ] ),
			$this->constructSummary( 'update-languages-short', 'en', null ),
		];

		yield 'add a german %1$s with an existing english %1$s' => [
			null,
			new TermList( [ new Term( 'en', 'ENGLISH-TERM' ) ] ),
			new TermList( [ new Term( 'de', 'GERMAN-TERM' ), new Term( 'en', 'ENGLISH-TERM' ) ] ),
			$this->constructSummary( 'update-languages-short', 'de', null ),
		];

		yield 'modify english %1$s' => [
			null,
			new TermList( [ new Term( 'en', 'ENGLISH-TERM' ) ] ),
			new TermList( [ new Term( 'en', 'MODIFIED-ENGLISH-TERM' ) ] ),
			$this->constructSummary( 'update-languages-short', 'en', null ),
		];

		yield 'delete english %1$s' => [
			null,
			new TermList( [ new Term( 'en', 'ENGLISH-TERM' ) ] ),
			new TermList(),
			$this->constructSummary( 'update-languages-short', 'en', null ),
		];
	}

	private function getLongTermsList(): array {
		$longLabelsList = [];
		$languages = WikibaseRepo::getTermsLanguages()->getLanguages();

		foreach ( $languages as $language ) {
			$longLabelsList[] = new Term( $language, "new term in {$language}" );
		}
		return $longLabelsList;
	}

	private function constructSummary( string $actionName, string $autoCommentArgs, ?string $userComment ): Summary {
		$summary = new Summary( 'wbeditentity', $actionName, null, [ $autoCommentArgs ] );
		$summary->setUserSummary( $userComment );
		return $summary;
	}

}
