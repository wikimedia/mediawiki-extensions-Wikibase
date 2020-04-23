<?php

namespace Wikibase\Repo\Tests\Parsers;

use Language;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use ValueParsers\ParserOptions;
use ValueParsers\Test\StringValueParserTest;
use ValueParsers\ValueParser;
use Wikibase\Repo\Parsers\MwEraParser;

/**
 * @covers \Wikibase\Repo\Parsers\MwEraParser
 *
 * @group ValueParsers
 * @group Wikibase
 * @group TimeParsers
 */
class MwEraParserTest extends StringValueParserTest {
	use PHPUnit4CompatTrait;

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

	protected function setUp() : void {
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

	protected function tearDown() : void {
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
			->with( $this->equalTo( MwEraParser::MESSAGE_KEY ) )
			->willReturn( '$1 v. Chr.' );

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

			[ '2019 v. Chr.', [ '-', '2019' ] ],
			[ 'September 2019 v. Chr.', [ '-', 'September 2019' ] ],
			[ '19. September 2019 v. Chr.', [ '-', '19. September 2019' ] ],
			[ '2019-09-19 v. Chr.', [ '-', '2019-09-19' ] ],

			[ 'foo BCE', [ '-', 'foo' ] ],
			[ 'foo v. Chr.', [ '-', 'foo' ] ],

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

}
