<?php

namespace Wikibase\Lib\Tests\ParserFunctions;

use MediaWiki\Language\Language;
use MediaWiki\Parser\Parser;
use Wikibase\Lib\ParserFunctions\CommaSeparatedList;

/**
 * @covers \Wikibase\Lib\ParserFunctions\CommaSeparatedList
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CommaSeparatedListTest extends \PHPUnit\Framework\TestCase {

	/** @var CommaSeparatedList */
	private $handler;

	protected function setUp(): void {
		parent::setUp();
		$this->handler = new CommaSeparatedList();
	}

	public function testHandle() {
		$language = $this->createMock( Language::class );
		$language->method( 'commaList' )->willReturnCallback( function ( $words ) {
			return implode( ', ', $words );
		} );
		$parser = $this->createMock( Parser::class );
		$parser->method( 'getTargetLanguage' )->willReturn( $language );
		$expectedCommaSeparatedList = "word1, word2, word3";
		$actualCommaSeparatedList = $this->handler->handle( $parser, "word1", "word2", "word3" );

		$this->assertEquals( $expectedCommaSeparatedList, $actualCommaSeparatedList );
	}
}
