<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\RestApi\DataAccess\TermLookupItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\TermLookupItemLabelsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermLookupItemLabelsRetrieverTest extends TestCase {

	private const ALL_TERM_LANGUAGES = [ 'de', 'en', 'ko' ];

	public function testGetLabels(): void {
		$itemId = new ItemId( 'Q123' );

		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getLabels' )
			->with( $itemId, self::ALL_TERM_LANGUAGES )
			->willReturn( [
				'en' => 'potato',
				'de' => 'Kartoffel',
				'ko' => '감자',
			] );

		$this->assertEquals(
			( $this->newLabelsRetriever( $termLookup ) )->getLabels( $itemId ),
			new Labels(
				new Label( 'en', 'potato' ),
				new Label( 'de', 'Kartoffel' ),
				new Label( 'ko', '감자' ),
			)
		);
	}

	public function testGivenTermLookupThrowsLookupException_returnsNull(): void {
		$itemId = new ItemId( 'Q123' );

		$termLookup = $this->createStub( TermLookup::class );
		$termLookup->method( 'getLabels' )
			->willThrowException( new TermLookupException( $itemId, [] ) );

		$this->assertNull( $this->newLabelsRetriever( $termLookup )->getLabels( $itemId ) );
	}

	private function newLabelsRetriever( TermLookup $termLookup ): TermLookupItemLabelsRetriever {
		return new TermLookupItemLabelsRetriever(
			$termLookup,
			new StaticContentLanguages( self::ALL_TERM_LANGUAGES )
		);
	}

}
