<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\Revision\RevisionRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Repo\Hooks\SummaryParsingPrefetchHelper;

/**
 * @covers \Wikibase\Repo\Hooks\SummaryParsingPrefetchHelper
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SummaryParsingPrefetchHelperTest extends TestCase {

	/** @var TermBuffer&MockObject */
	private $termBuffer;

	protected function setUp(): void {
		parent::setUp();
		$this->termBuffer = $this->createMock( TermBuffer::class );
	}

	/**
	 * @dataProvider rowDataProvider
	 */
	public function testPrefetchTermsForMentionedEntities( callable $rowsFactory, array $expected ) {
		$rows = $rowsFactory( $this );
		$helper = new SummaryParsingPrefetchHelper( $this->termBuffer );

		$expectedEntityIds = [];
		$entityIdParser = new BasicEntityIdParser();
		foreach ( $expected as $idSerialization ) {
			$expectedEntityIds[] = $entityIdParser->parse( $idSerialization );
		}

		$this->termBuffer->expects( $expectedEntityIds ? $this->once() : $this->never() )
			->method( 'prefetchTerms' )
			->with(
				$expectedEntityIds,
				[ TermTypes::TYPE_LABEL ],
				[ 'en' ]
			);

		$helper->prefetchTermsForMentionedEntities( $rows, [ 'en' ], [ TermTypes::TYPE_LABEL ] );
	}

	/**
	 * @dataProvider rowDataProvider
	 */
	public function testShouldExtractProperties( callable $rowsFactory, array $expected ) {
		$rows = $rowsFactory( $this );
		$helper = new SummaryParsingPrefetchHelper( $this->termBuffer );
		$actualOutput = $helper->extractSummaryMentions( $rows );

		$this->assertSameSize( $expected, $actualOutput );

		$stringOutput = array_map( fn ( $entityId ) => $entityId->getSerialization(), $actualOutput );

		$this->assertSame( sort( $expected ), sort( $stringOutput ) );
	}

	public static function rowDataProvider() {
		return [
			'Property:P31' => [
				fn () => [ (object)[ 'rev_comment_text' => '[[Property:P31]]' ] ],
				[ 'P31' ],
			],
			'Links to main namespace' => [
				fn () => [ (object)[ 'rev_comment_text' => '[[P11]] [[Q22]][[P33]]' ] ],
				[ 'P11', 'Q22', 'P33' ],
			],
			'wdbeta:Special:EntityPage/P123' => [
				fn () => [
					(object)[ 'rev_comment_text' => '[[wdbeta:Special:EntityPage/P123]]' ],
					(object)[ 'rev_comment_text' => '[[Property:P1234]]' ],
				],
				[ 'P123', 'P1234' ],
			],
			'Some other comment not parsed as link' => [
				fn () => [
					(object)[ 'rev_comment_text' => 'Great update /P14 stockholm' ],
					(object)[ 'rc_comment_text' => 'P31]] [[PP4]] [[unrelated|P5]] [[P10000000000]]' ],
					(object)[ 'rc_comment_text' => '[P31:P31]' ],
				],
				[],
			],
			'Recentchanges object' => [
				fn () => [ (object)[ 'rc_comment_text' => '[[Property:P31]]' ] ],
				[ 'P31' ],
			],
			'RevisionRecord match' => [
				fn ( self $self ) => [ $self->mockRevisionRecord( 'something [[Property:P31]]' ) ],
				[ 'P31' ],
			],
			'null' => [ fn () => [ (object)[ 'rc_comment_text' => null ] ], [] ],
		];
	}

	private function mockRevisionRecord( string $commentString ): RevisionRecord {
		$mock = $this->createMock( RevisionRecord::class );
		$mock->method( 'getComment' )->willReturn( (object)[ 'text' => $commentString ] );
		return $mock;
	}

}
