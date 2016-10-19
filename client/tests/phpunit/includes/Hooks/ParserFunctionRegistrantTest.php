<?php

namespace Wikibase\Client\Tests\Hooks;

use Parser;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;
use Wikibase\Client\WikibaseClient;

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

	public function parserFunctionsProvider() {
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
	 * @dataProvider parserFunctionsProvider
	 */
	public function testRegisterParserFunctions( $allowDataTransclusion, array $expected ) {
		$parser = new Parser( [ 'class' => 'Parser' ] );

		// TODO: Remove the feature flag when not needed any more!
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$enabled = $settings->getSetting( 'enableStatementsParserFunction' );
		$settings->setSetting( 'enableStatementsParserFunction', true );

		$registrant = new ParserFunctionRegistrant( $allowDataTransclusion );
		$registrant->register( $parser );
		$actual = $parser->getFunctionHooks();

		$settings->setSetting( 'enableStatementsParserFunction', $enabled );

		$this->assertSame( $expected, $actual );
	}

}
