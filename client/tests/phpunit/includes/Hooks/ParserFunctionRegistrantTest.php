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
 * @author Thiemo Kreuz
 */
class ParserFunctionRegistrantTest extends PHPUnit_Framework_TestCase {

	public function parserFunctionsProvider() {
		return [
			[
				'allowDataTransclusion' => false,
				[
					'noexternallanglinks',
				]
			],
			[
				'allowDataTransclusion' => true,
				[
					'noexternallanglinks',
					'property',
					'statements',
				]
			],
		];
	}

	/**
	 * @dataProvider parserFunctionsProvider
	 */
	public function testRegisterParserFunctions(
		$allowDataTransclusion,
		array $expected
	) {
		$parser = new Parser( [ 'class' => 'Parser' ] );

		$registrant = new ParserFunctionRegistrant( $allowDataTransclusion );
		$registrant->register( $parser );
		$actual = $parser->getFunctionHooks();

		$this->assertSame( $expected, $actual );
	}

}
