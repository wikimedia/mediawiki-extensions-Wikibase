<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use MediaWiki\Revision\RevisionRecord;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Repo\FederatedProperties\ApiRequestException;
use Wikibase\Repo\FederatedProperties\SummaryParsingPrefetchHelper;

/**
 * @covers \Wikibase\Repo\FederatedProperties\SummaryParsingPrefetchHelper
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SummaryParsingPrefetchHelperTest extends TestCase {

	private $prefetchingLookup;

	protected function setUp(): void {
		parent::setUp();
		$this->prefetchingLookup = $this->createMock( PrefetchingTermLookup::class );
	}

	/**
	 * @dataProvider rowDataProvider
	 */
	public function testParsesAndPrefetchesComments( array $rows, array $expectedProperties ) {
		$helper = new SummaryParsingPrefetchHelper( $this->prefetchingLookup );

		$expectedPropertyIds = [];
		foreach ( $expectedProperties as $propertyString ) {
			$expectedPropertyIds[] = new NumericPropertyId( $propertyString );
		}

		$this->prefetchingLookup->expects( empty( $expectedPropertyIds ) ? $this->never() : $this->once() )
			->method( 'prefetchTerms' )
			->with(
				$expectedPropertyIds,
				[ TermTypes::TYPE_LABEL ],
				[ 'en' ]
			);

		$helper->prefetchFederatedProperties( $rows, [ 'en' ], [ TermTypes::TYPE_LABEL ] );
	}

	public function testShouldNotFatalOnFailedRequests() {
		$helper = new SummaryParsingPrefetchHelper( $this->prefetchingLookup );
		$rows = [ (object)[ 'rev_comment_text' => '[[Property:P31]]' ] ];

		$this->prefetchingLookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->willThrowException( new ApiRequestException( 'oh no!' ) );

		$this->expectWarning();
		$helper->prefetchFederatedProperties( $rows, [ 'en' ], [ TermTypes::TYPE_LABEL ] );
	}

	/**
	 * @dataProvider rowDataProvider
	 */
	public function testShouldExtractProperties( array $rows, array $expectedProperties ) {
		$helper = new SummaryParsingPrefetchHelper( $this->prefetchingLookup );
		$actualOutput = $helper->extractSummaryProperties( $rows );

		$this->assertSameSize( $expectedProperties, $actualOutput );

		$stringOutput = array_map( function ( $propId ) {
			return $propId->getSerialization();
		}, $actualOutput );

		$this->assertSame( sort( $expectedProperties ), sort( $stringOutput ) );
	}

	public function rowDataProvider() {
		return [
			'Property:P1' => [ [ (object)[ 'rev_comment_text' => '[[Property:P31]]' ] ], [ 'P31' ] ],
			'wdbeta:Special:EntityPage/P123' => [
				[
					(object)[ 'rev_comment_text' => '[[wdbeta:Special:EntityPage/P123]]' ],
					(object)[ 'rev_comment_text' => '[[Property:P1234]]' ],
				],
				[ 'P123', 'P1234' ],
			],
			'Some other comment not parsed as link' => [
				[
					(object)[ 'rev_comment_text' => 'Great update /P14 stockholm' ],
					(object)[ 'rc_comment_text' => '[[P31]]' ],
					(object)[ 'rc_comment_text' => 'P31]]' ],
					(object)[ 'rc_comment_text' => '[P31:P31]' ],
				],
				[],
			],
			'Recentchanges object' => [ [ (object)[ 'rc_comment_text' => '[[Property:P31]]' ] ], [ 'P31' ] ],
			'RevisionRecord match' => [ [ $this->mockRevisionRecord( 'something [[Property:P31]]' ) ], [ 'P31' ] ],
			'RevisionRecord no match' => [ [ $this->mockRevisionRecord( 'something [[P31]]' ) ], [] ],
			'null' => [ [ (object)[ 'rc_comment_text' => null ] ], [] ],
		];
	}

	private function mockRevisionRecord( string $commentString ) {
		$mock = $this->createMock( RevisionRecord::class );
		$mock->method( 'getComment' )->willreturn( (object)[ 'text' => $commentString ] );
		return $mock;
	}

}
