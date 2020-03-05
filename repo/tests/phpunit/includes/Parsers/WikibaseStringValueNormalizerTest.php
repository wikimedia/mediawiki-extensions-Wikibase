<?php

namespace Wikibase\Repo\Tests\Parsers;

use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\Parsers\WikibaseStringValueNormalizer;

/**
 * @covers \Wikibase\Repo\Parsers\WikibaseStringValueNormalizer
 *
 * @group ValueParsers
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseStringValueNormalizerTest extends \PHPUnit\Framework\TestCase {

	public function testNormalize() {
		$input = 'Kittens';

		$mock = $this->createMock( StringNormalizer::class );
		$mock->expects( $this->once() )
			->method( 'trimToNFC' )
			->with( $input );

		$normalizer = new WikibaseStringValueNormalizer( $mock );
		$normalizer->normalize( $input );
	}

}
