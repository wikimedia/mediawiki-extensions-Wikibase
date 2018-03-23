<?php

namespace Wikibase\Repo\Tests\Parsers;

use Wikibase\Repo\Parsers\WikibaseStringValueNormalizer;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\Parsers\WikibaseStringValueNormalizer
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

		$mock = $this->getMock( StringNormalizer::class );
		$mock->expects( $this->once() )
			->method( 'trimToNFC' )
			->with( $input );

		$normalizer = new WikibaseStringValueNormalizer( $mock );
		$normalizer->normalize( $input );
	}

}
