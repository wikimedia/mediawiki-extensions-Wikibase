<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Summary;
use Wikibase\Repo\RestApi\Domain\Model\PatchPropertyEditSummary;
use Wikibase\Repo\RestApi\Infrastructure\WholeEntityEditSummaryToFormattableSummaryConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\WholeEntityEditSummaryToFormattableSummaryConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WholeEntityEditSummaryToFormattableSummaryConverterTest extends TestCase {
	/**
	 * @dataProvider propertyEditSummaryProvider
	 */
	public function testPatchPropertyEditSummary( PatchPropertyEditSummary $editSummary, Summary $expectedSummary ): void {
		$editSummaryFormatter = new WholeEntityEditSummaryToFormattableSummaryConverter();
		$this->assertEquals( $expectedSummary, $editSummaryFormatter->newSummaryForPropertyPatch( $editSummary ) );
	}

	public static function propertyEditSummaryProvider(): Generator {
		$userComment = 'user comment';
		$propertyId = new NumericPropertyId( 'P123' );
		$originalProperty = new Property( $propertyId, new Fingerprint(), 'string', null );

		yield 'patch property with labels and user comment' => [
			PatchPropertyEditSummary::newSummary(
				$userComment,
				$originalProperty,
				new Property(
					$propertyId,
					new Fingerprint( new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ) ),
					'string',
					null
				)
			),
			self::constructSummary( 'update-languages-short', [ 'de, en' ], 'user comment' ),
		];

		yield 'patch property with just statement and no user comment' => [
			PatchPropertyEditSummary::newSummary(
				null,
				$originalProperty,
				new Property(
					$propertyId,
					new Fingerprint(),
					'string',
					new StatementList( NewStatement::noValueFor( 'P123' )->build() )
				)
			),
			self::constructSummary( 'update', [], null ),
		];

		yield 'patch property with statement, labels, descriptions, and user comment' => [
			PatchPropertyEditSummary::newSummary(
				$userComment,
				$originalProperty,
				new Property(
					$propertyId,
					new Fingerprint(
						new TermList( [ new Term( 'en', 'potato' ), new Term( 'ar', 'بطاط' ) ] ),
						new TermList( [ new Term( 'en', 'vegetable' ), new Term( 'ar', 'الخضروات' ) ] )
					),
					'string',
					new StatementList( NewStatement::noValueFor( 'P123' )->build() )
				)
			),
			self::constructSummary( 'update-languages-and-other-short', [ 'ar, en' ], 'user comment' ),
		];

		yield 'patch property with different languages of labels and descriptions ' => [
			PatchPropertyEditSummary::newSummary(
				$userComment,
				$originalProperty,
				new Property(
					$propertyId,
					new Fingerprint(
						new TermList( [
							new Term( 'en', 'Potato' ),
							new Term( 'de', 'Kartoffel' ),
							new Term( 'ar', 'بطاط' ),
						] ),
						new TermList( [ new Term( 'en', 'vegetable' ), new Term( 'ar', 'الخضروات' ) ] )
					),
					'string',
				)
			),
			self::constructSummary( 'update-languages-short', [ 'ar, de, en' ], 'user comment' ),
		];

		yield 'patch property with long labels list, statement, and no user comment' => [
			PatchPropertyEditSummary::newSummary(
				null,
				$originalProperty,
				new Property(
					$propertyId,
					new Fingerprint( new TermList( self::getLongTermsList() ) ),
					'string',
					new StatementList( NewStatement::noValueFor( 'P123' )->build() )
				)
			),
			self::constructSummary(
				'update-languages-and-other',
				[ (string)count( self::getLongTermsList() ) ],
				null
			),
		];

		yield 'patch property with long labels list and user comment' => [
			PatchPropertyEditSummary::newSummary(
				$userComment,
				$originalProperty,
				new Property(
					$propertyId,
					new Fingerprint(
						new TermList( self::getLongTermsList() )
					),
					'string',
					null
				)
			),
			self::constructSummary(
				'update-languages',
				[ (string)count( self::getLongTermsList() ) ],
				'user comment'
			),
		];
	}

	private static function constructSummary( string $actionName, array $autoCommentArgs, ?string $userComment ): Summary {
		$summary = new Summary( 'wbeditentity', $actionName, null, $autoCommentArgs );
		$summary->setUserSummary( $userComment );
		return $summary;
	}

	private static function getLongTermsList(): array {
		$longLabelsList = [];
		$languages = WikibaseRepo::getTermsLanguages()->getLanguages();

		foreach ( $languages as $language ) {
			$longLabelsList[] = new Term( $language, "new term in {$language}" );
		}
		return $longLabelsList;
	}

}
