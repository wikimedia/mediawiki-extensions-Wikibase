<?php

namespace Wikibase\Client\Tests\Hooks;

use Parser;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;

/**
 * @covers Wikibase\Client\Hooks\ParserFunctionRegistrant
 */
class ParserFunctionRegistrantTest extends \PHPUnit_Framework_TestCase {

	public function testRegisterPropertyParserFunctions() {
		$parser = $this->newParser();

		$registrant = new ParserFunctionRegistrant(
			$this->getPropertyParserFunctionHandler(),
			true
		);

		$registrant->register( $parser );

		$functionHooks = $parser->getFunctionHooks();

		$this->assertEquals( array( 'noexternallanglinks', 'property' ), $functionHooks );
	}

	public function testRegisterOnlyNoExternalLangLinksFuntion() {
		$parser = $this->newParser();

		$registrant = new ParserFunctionRegistrant(
			$this->getPropertyParserFunctionHandler(),
			false
		);

		$registrant->register( $parser );

		$functionHooks = $parser->getFunctionHooks();

		$this->assertEquals( array( 'noexternallanglinks' ), $functionHooks );
	}

	private function newParser() {
		$parserConfig = array( 'class' => 'Parser' );
		return new Parser( $parserConfig );
	}

	private function getPropertyParserFunctionHandler() {
		$propertyParserFunctionHandler = $this->getMockBuilder(
				'\Wikibase\DataAccess\PropertyParserFunctionHandler'
			)->disableOriginalConstructor()
			->getMock();

		$propertyParserFunctionHandler->expects( $this->any() )
			->method( 'handle' )
			->will( $this->returnValue( 'prop!' ) );

		return $propertyParserFunctionHandler;
	}

}
