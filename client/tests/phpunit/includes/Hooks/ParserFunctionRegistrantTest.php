<?php

namespace Wikibase\Client\Tests\Hooks;

use Parser;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;

/**
 * @covers Wikibase\Client\Hooks\ParserFunctionRegistrant
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class ParserFunctionRegistrantTest extends \PHPUnit\Framework\TestCase {

	public function parserFunctionsProvider() {
		return [
			[
				[
					'allowDataTransclusion' => false,
					'allowLocalShortDesc' => false,
				],
				[
					'noexternallanglinks',
				],
			],
			[
				[
					'allowDataTransclusion' => true,
					'allowLocalShortDesc' => false,
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
					'allowLocalShortDesc' => true,
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

		list( $allowDataTransclusion, $allowLocalShortDesc ) = array_values( $constructorArgs );
		$registrant = new ParserFunctionRegistrant( $allowDataTransclusion, $allowLocalShortDesc );
		$registrant->register( $parser );
		$actual = $parser->getFunctionHooks();

		$this->assertSame( $expected, $actual );
	}

}
