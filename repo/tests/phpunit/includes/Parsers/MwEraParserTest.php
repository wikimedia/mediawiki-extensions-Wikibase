<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\DataValue;
use Language;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\Parsers\MwEraParser;

/**
 * @covers \Wikibase\Repo\Parsers\MwEraParser
 *
 * @group ValueParsers
 * @group Wikibase
 * @group TimeParsers
 * @license GPL-2.0-or-later
 */
class MwEraParserTest extends \PHPUnit\Framework\TestCase {

	/** @var LanguageFactory */
	private $oldLangFactory;

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return MwEraParser
	 */
	protected function getInstance() {
		$options = new ParserOptions();
		$options->setOption( ValueParser::OPT_LANG, 'de' );

		return new MwEraParser( $options );
	}

	protected function setUp(): void {
		parent::setUp();

		$services = MediaWikiServices::getInstance();
		$this->oldLangFactory = $services->getLanguageFactory();
		$stub = $this->createMock( LanguageFactory::class );
		$stub->method( 'getLanguage' )->willReturn( $this->getLanguage() );
		$services->disableService( 'LanguageFactory' );
		$services->redefineService( 'LanguageFactory',
			function () use ( $stub ) {
				return $stub;
			}
		);
	}

	protected function tearDown(): void {
		MediaWikiServices::getInstance()->resetServiceForTesting( 'LanguageFactory' );
		MediaWikiServices::getInstance()->redefineService(
			'LanguageFactory',
			function () {
				return $this->oldLangFactory;
			}
		);
		parent::tearDown();
	}

	private function getLanguage() {
		$code = 'de';
		$lang = $this->createMock( Language::class );

		$lang->method( 'getCode' )
			->willReturn( $code );

		$lang->method( 'getMessage' )
			->willReturnMap( [
				[ MwEraParser::BCE_MESSAGE_KEY, '$1 v. Chr.' ],
				[ MwEraParser::CE_MESSAGE_KEY, '$1 n. Chr.' ],
			] );

		return $lang;
	}

	/**
	 * @see ValueParserTestBase::requireDataValue
	 *
	 * @return bool
	 */
	protected function requireDataValue() {
		return false;
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 */
	public function validInputProvider() {
		return [
			[ '2019 BCE', [ '-', '2019' ] ],
			[ 'September 2019 BCE', [ '-', 'September 2019' ] ],
			[ 'September 19th, 2019 BCE', [ '-', 'September 19th, 2019' ] ],
			[ '2019-09-19 BCE', [ '-', '2019-09-19' ] ],

			[ '2019 CE', [ '+', '2019' ] ],
			[ 'September 2019 CE', [ '+', 'September 2019' ] ],
			[ 'September 19th, 2019 CE', [ '+', 'September 19th, 2019' ] ],
			[ '2019-09-19 CE', [ '+', '2019-09-19' ] ],

			[ '2019 v. Chr.', [ '-', '2019' ] ],
			[ 'September 2019 v. Chr.', [ '-', 'September 2019' ] ],
			[ '19. September 2019 v. Chr.', [ '-', '19. September 2019' ] ],
			[ '2019-09-19 v. Chr.', [ '-', '2019-09-19' ] ],

			[ '2019 n. Chr.', [ '+', '2019' ] ],
			[ 'September 2019 n. Chr.', [ '+', 'September 2019' ] ],
			[ '19. September 2019 n. Chr.', [ '+', '19. September 2019' ] ],
			[ '2019-09-19 n. Chr.', [ '+', '2019-09-19' ] ],

			[ 'foo BCE', [ '-', 'foo' ] ],
			[ 'foo v. Chr.', [ '-', 'foo' ] ],
			[ 'foo CE', [ '+', 'foo' ] ],
			[ 'foo n. Chr.', [ '+', 'foo' ] ],

			[ 'foo v.Chr.', [ '+', 'foo v.Chr.' ] ],

			/*
			 * Test cases copy-pasted from Time/tests/ValueParsers/EraParserTest.php
			 * (0ce1e1fc10fbf1da232f88235adb609c924f3e79)
			 */
			// Strings with no explicit era should be echoed
			[ '1', [ '+', '1' ] ],
			[ '1 000 000', [ '+', '1 000 000' ] ],
			[ 'non-matching string', [ '+', 'non-matching string' ] ],
			// Strings with an era that should be split of
			[ '+100', [ '+', '100' ] ],
			[ '-100', [ '-', '100' ] ],
			[ '   -100', [ '-', '100' ] ],
			[ '100BC', [ '-', '100' ] ],
			[ '100 BC', [ '-', '100' ] ],
			[ '100 BCE', [ '-', '100' ] ],
			[ '100 AD', [ '+', '100' ] ],
			[ '100 A. D.', [ '+', '100' ] ],
			[ '   100   B.   C.   ', [ '-', '100' ] ],
			[ '   100   Common   Era   ', [ '+', '100' ] ],
			[ '100 CE', [ '+', '100' ] ],
			[ '100CE', [ '+', '100' ] ],
			[ '+100', [ '+', '100' ] ],
			[ '100 Common Era', [ '+', '100' ] ],
			[ '100 Current Era', [ '+', '100' ] ],
			[ '100 Christian Era', [ '+', '100' ] ],
			[ '100Common Era', [ '+', '100' ] ],
			[ '100 Before Common Era', [ '-', '100' ] ],
			[ '100 Before Current Era', [ '-', '100' ] ],
			[ '100 Before Christian Era', [ '-', '100' ] ],
			[ '1 July 2013 Before Common Era', [ '-', '1 July 2013' ] ],
			[ 'June 2013 Before Common Era', [ '-', 'June 2013' ] ],
			[ '10-10-10 Before Common Era', [ '-', '10-10-10' ] ],
			[ 'FooBefore Common Era', [ '-', 'Foo' ] ],
			[ 'Foo Before Common Era', [ '-', 'Foo' ] ],
			[ '-1 000 000', [ '-', '1 000 000' ] ],
			[ '1 000 000 B.C.', [ '-', '1 000 000' ] ],
		];
	}

	public function testSetAndGetOptions() {
		$parser = $this->getInstance();

		$parser->setOptions( new ParserOptions() );

		$this->assertEquals( new ParserOptions(), $parser->getOptions() );

		$options = new ParserOptions();
		$options->setOption( 'someoption', 'someoption' );

		$parser->setOptions( $options );

		$this->assertEquals( $options, $parser->getOptions() );
	}

	/**
	 * @dataProvider validInputProvider
	 * @param mixed $value
	 * @param mixed $expected
	 * @param ValueParser|null $parser
	 */
	public function testParseWithValidInputs( $value, $expected, ValueParser $parser = null ) {
		if ( $parser === null ) {
			$parser = $this->getInstance();
		}

		$this->assertSmartEquals( $expected, $parser->parse( $value ) );
	}

	/**
	 * @param DataValue|mixed $expected
	 * @param DataValue|mixed $actual
	 */
	private function assertSmartEquals( $expected, $actual ) {
		if ( $this->requireDataValue() ) {
			if ( $expected instanceof DataValue && $actual instanceof DataValue ) {
				$msg = "testing equals():\n"
					. preg_replace( '/\s+/', ' ', print_r( $actual->toArray(), true ) ) . " should equal\n"
					. preg_replace( '/\s+/', ' ', print_r( $expected->toArray(), true ) );
			} else {
				$msg = 'testing equals()';
			}

			$this->assertTrue( $expected->equals( $actual ), $msg );
		} else {
			$this->assertEquals( $expected, $actual );
		}
	}

}
