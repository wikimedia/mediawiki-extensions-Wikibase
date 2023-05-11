<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Summary;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Infrastructure\LabelsEditSummaryToFormattableSummaryConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\LabelsEditSummaryToFormattableSummaryConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsEditSummaryToFormattableSummaryConverterTest extends TestCase {

	/**
	 * @dataProvider labelsEditSummaryProvider
	 */
	public function testConvert( LabelsEditSummary $editSummary, Summary $formattedSummary ): void {
		$editSummaryFormatter = new LabelsEditSummaryToFormattableSummaryConverter();
		$this->assertEquals( $formattedSummary, $editSummaryFormatter->convert( $editSummary ) );
	}

	private function constructSummary( string $actionName, string $autoCommentArgs, ?string $userComment ): Summary {
		$summary = new Summary( 'wbeditentity', $actionName, null, [ $autoCommentArgs ] );
		$summary->setUserSummary( $userComment );
		return $summary;
	}

	private function getLongLabelsList(): array {
		$longLabelsList = [];
		$languages = WikibaseRepo::getTermsLanguages()->getLanguages();

		foreach ( $languages as $language ) {
			$longLabelsList[] = new Term( $language, "new label in {$language}" );
		}
		return $longLabelsList;
	}

	public function labelsEditSummaryProvider(): Generator {
		yield 'replace many labels' => [
			LabelsEditSummary::newPatchSummary(
				'patch user comment',
				new TermList(),
				new TermList( $this->getLongLabelsList() )
			),
			$this->constructSummary(
				'update-languages',
				(string)count( $this->getLongLabelsList() ),
				'patch user comment'
			),
		];

		yield 'replace labels with user comment' => [
			LabelsEditSummary::newPatchSummary(
				'patch user comment',
				new TermList(),
				new TermList( [
					new Term( 'en', 'LABEL-TEXT' ),
					new Term( 'de', 'LABEL-TEXT-IN-GERMAN' ),
				] )
			),
			$this->constructSummary(
				'update-languages-short',
				'de, en',
				'patch user comment'
			),
		];

		yield 'add english label' => [
			LabelsEditSummary::newPatchSummary(
				null,
				new TermList(),
				new TermList( [ new Term( 'en', 'ENGLISH-LABEL' ) ] )
			),
			$this->constructSummary( 'update-languages-short', 'en', null ),
		];

		yield 'add a german label with an existing english label' => [
			LabelsEditSummary::newPatchSummary(
				null,
				new TermList( [ new Term( 'en', 'ENGLISH-LABEL' ) ] ),
				new TermList( [
					new Term( 'de', 'GERMAN-LABEL' ),
					new Term( 'en', 'ENGLISH-LABEL' ),
				] ),
			),
			$this->constructSummary( 'update-languages-short', 'de', null ),
		];

		yield 'modify english label' => [
			LabelsEditSummary::newPatchSummary(
				null,
				new TermList( [ new Term( 'en', 'ENGLISH-LABEL' ) ] ),
				new TermList( [ new Term( 'en', 'MODIFIED-ENGLISH-LABEL' ) ] )
			),
			$this->constructSummary( 'update-languages-short', 'en', null ),
		];

		yield 'delete english label' => [
			LabelsEditSummary::newPatchSummary(
				null,
				new TermList( [ new Term( 'en', 'ENGLISH-LABEL' ) ] ),
				new TermList()
			),
			$this->constructSummary( 'update-languages-short', 'en', null ),
		];
	}

}
