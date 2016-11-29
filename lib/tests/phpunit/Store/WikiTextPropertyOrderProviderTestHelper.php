<?php

namespace Wikibase\Lib\Tests\Store;

/**
 * Helper class for tests for WikiTextPropertyOrderProvider subclasses.
 *
 * @license GPL-2.0+
 * @author Lucie-Aimée Kaffee
 * @author Marius Hoch
 */
class WikiTextPropertyOrderProviderTestHelper {

	public static function provideGetPropertyOrder() {
		return [
			'simple match' => [
				"* P1 \n"
				. "*P133 \n"
				. "* p5", // Testing for lower case property IDs
				[ 'P1' => 0, 'P133' => 1, 'P5' => 2 ]
			],
			'strip multiline comment' => [
				"* P1 \n"
				. "<!-- * P133 \n"
				. "* P5 -->",
				[ 'P1' => 0 ]
			],
			'muliple comments' => [
				"* P1 \n"
				. "<!-- * P133 --> \n"
				. "* <!-- P5 -->",
				[ 'P1' => 0 ]
			],
			'bullet point glibberish' => [
				"* P1 \n"
				. "* P133 \n"
				. "* P5 Unicorns are all \n"
				. "*  very beautiful!"
				. "** This is a subheading",
				[ 'P1' => 0, 'P133' => 1, 'P5' => 2 ]
			],
			'additional text' => [
				"* P1 \n"
				. "* P133 \n"
				. "* P5 Unicorns are all \n"
				. "very beautiful!",
				[ 'P1' => 0, 'P133' => 1, 'P5' => 2 ]
			],
		];
	}

}
