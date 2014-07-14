<?php

namespace Wikibase\Client\Tests\Hooks;

use Parser;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;

/**
 * @covers Wikibase\Client\Hooks\ParserFunctionRegistrant
 */
class ParserFunctionRegistrantTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider registerParserFunctionsProvider
	 */
	public function testRegisterParserFunctions( array $expectedFunctions,
		array $notExpectedFunctions, $allowDataTransclusion
	) {
		$parser = $this->newParser();

		$registrant = new ParserFunctionRegistrant( $allowDataTransclusion );

		try {
			$registrant->register( $parser );
		} catch ( \MWException $ex ) {
			// invalid magic word, ignore
		}

		$functionHooks = $parser->getFunctionHooks();

		foreach( $expectedFunctions as $expectedFunction ) {
			$this->assertContains( $expectedFunction, $functionHooks );
		}

		foreach( $notExpectedFunctions as $notExpectedFunction ) {
			$this->assertNotContains( $notExpectedFunction, $functionHooks );
		}
	}

	public function registerParserFunctionsProvider() {
		return array(
			array(
				array( 'property', 'noexternallanglinks' ),
				array(),
				true
			),
			array(
				array( 'noexternallanglinks' ),
				array( 'property' ),
				false
			)
		);
	}

	private function newParser() {
		$parserConfig = array( 'class' => 'Parser' );
		return new Parser( $parserConfig );
	}

}
