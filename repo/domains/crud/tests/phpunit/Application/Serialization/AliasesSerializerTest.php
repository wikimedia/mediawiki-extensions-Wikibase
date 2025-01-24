<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Serialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\AliasesInLanguage;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AliasesSerializerTest extends TestCase {

	/**
	 * @dataProvider aliasesProvider
	 */
	public function testSerialize( Aliases $aliases, ArrayObject $serialization ): void {
		$this->assertEquals(
			$serialization,
			( new AliasesSerializer() )->serialize( $aliases )
		);
	}

	public static function aliasesProvider(): Generator {
		yield 'empty' => [
			new Aliases(),
			new ArrayObject( [] ),
		];

		yield 'single aliasesInLanguage' => [
			new Aliases( new AliasesInLanguage( 'en', [ 'spud' ] ) ),
			new ArrayObject( [ 'en' => [ 'spud' ] ] ),
		];

		yield 'multiple aliasesInLanguage' => [
			new Aliases(
				new AliasesInLanguage( 'en', [ 'spud' ] ),
				new AliasesInLanguage( 'de', [ 'Erdapfel', 'Grundbirne' ] ),
			),
			new ArrayObject( [
				'en' => [ 'spud' ],
				'de' => [ 'Erdapfel', 'Grundbirne' ],
			] ),
		];
	}

}
