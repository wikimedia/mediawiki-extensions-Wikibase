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
				[
					'allowDataTransclusion' => false,
					'allowLocalDescription' => false,
				],
				[
					'noexternallanglinks',
				],
			],
			[
				[
					'allowDataTransclusion' => true,
					'allowLocalDescription' => false,
				],
				[
					'noexternallanglinks',
					'property',
					'statements',
				],
			],
			[
				[
					'allowDataTransclusion' => false,
					'allowLocalDescription' => true,
				],
				[
					'noexternallanglinks',
					'shortdesc',
				],
			],
		];
	}

	/**
	 * @dataProvider parserFunctionsProvider
	 */
	public function testRegisterParserFunctions(
		$constructorArgs,
		array $expected
	) {
		$parser = new Parser( [ 'class' => 'Parser' ] );

		list( $allowDataTransclusion, $allowLocalDescription ) = $constructorArgs;
		$registrant = new ParserFunctionRegistrant( $allowDataTransclusion, $allowLocalDescription );
		$registrant->register( $parser );
		$actual = $parser->getFunctionHooks();

		$this->assertSame( $expected, $actual );
	}

}
