<?php

namespace Wikibase\Client\Tests\Hooks;

use Parser;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;

/**
 * @covers Wikibase\Client\Hooks\ParserFunctionRegistrant
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class ParserFunctionRegistrantTest extends PHPUnit_Framework_TestCase {

	public function Provider() {
		return [
			[
				false,
				[
					'noexternallanglinks',
				]
			],
			[
				true,
				[
					'noexternallanglinks',
					'property',
					'statements',
				]
			],
		];
	}

	/**
	 * @dataProvider Provider
	 */
	public function testRegisterPropertyParserFunctions( $allowDataTransclusion, array $expected ) {
		$parser = new Parser( [ 'class' => 'Parser' ] );

		$registrant = new ParserFunctionRegistrant( $allowDataTransclusion );
		$registrant->register( $parser );

		$this->assertSame( $expected, $parser->getFunctionHooks() );
	}

}
