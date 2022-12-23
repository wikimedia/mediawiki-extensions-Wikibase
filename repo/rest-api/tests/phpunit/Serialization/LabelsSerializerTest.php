<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Serialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Serialization\LabelsSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\LabelsSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsSerializerTest extends TestCase {

	/**
	 * @dataProvider labelsProvider
	 */
	public function testSerialize( TermList $labels, ArrayObject $serialization ): void {
		$this->assertEquals(
			( new LabelsSerializer() )->serialize( $labels ),
			$serialization
		);
	}

	public function labelsProvider(): Generator {
		yield 'empty' => [
			new TermList(),
			new ArrayObject( [] ),
		];

		yield 'multiple labels' => [
			new TermList( [
				new Term( 'en', 'potato' ),
				new Term( 'ko', '감자' ),
			] ),
			new ArrayObject( [
				'en' => 'potato',
				'ko' => '감자',
			] ),
		];
	}

}
