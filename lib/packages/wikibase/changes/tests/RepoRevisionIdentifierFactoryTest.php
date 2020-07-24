<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Changes;

use Exception;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Changes\RepoRevisionIdentifierFactory;

/**
 * @covers \Wikibase\Lib\Changes\RepoRevisionIdentifierFactory
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class RepoRevisionIdentifierFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testNewFromArray() {
		$factory = new RepoRevisionIdentifierFactory();
		$fromArray = $factory->newFromArray( $this->newRepoRevisionIdentifier()->toArray() );

		$this->assertEquals( $this->newRepoRevisionIdentifier(), $fromArray );
		$this->assertSame( $this->newRepoRevisionIdentifier()->toArray(), $fromArray->toArray() );
	}

	public function testNewFromArray_invalidArrayFormatVersion() {
		$factory = new RepoRevisionIdentifierFactory();
		$data = $this->newRepoRevisionIdentifier()->toArray();
		$data['arrayFormatVersion'] = 2;

		$this->expectException( Exception::class );
		$factory->newFromArray( $data );
	}

	private function newRepoRevisionIdentifier() {
		return new RepoRevisionIdentifier(
			'Q12',
			'20200302125300',
			123
		);
	}

}
