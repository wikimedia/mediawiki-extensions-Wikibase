<?php

namespace Wikibase\DataAccess\Tests;

use Parser;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;

/**
 * @covers Wikibase\Client\Hooks\ParserFunctionRegistrant
 */
class ParserFunctionRegistrantTest extends \PHPUnit_Framework_TestCase {

	public function testRegisterParserFunctions() {
		$parserConfig = array( 'class' => 'Parser' );
		$parser = new Parser( $parserConfig );

		$registrant = new ParserFunctionRegistrant(
			$this->getPropertyParserFunctionHandler(),
			true
		);

		try {
			$registrant->register( $parser );
		} catch ( \MWException $ex ) {
			// invalid magic word, ignore
		}

		$functionHooks = $parser->getFunctionHooks();

		$this->assertContains( 'property', $functionHooks );
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
