<?php

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use Wikibase\Lib\RepositoryDefinitions;

/**
 * @covers Wikibase\Lib\RepositoryDefinitions
 *
 * @license GPL-2.0+
 */
class RepositoryDefinitionsTest extends \PHPUnit_Framework_TestCase {

	public function provideInvalidConstructorArguments() {
		return [
			'repository name containing colon' => [ [ 'fo:o' => [] ] ],
			'repository definition not an array' => [ [ '' => 'string' ] ],
			'no settings for the local repository' => [ [ 'foo' => [ 'database' => 'foodb' ] ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidArguments_constructorThrowsException( array $args ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new RepositoryDefinitions( $args );
	}

	private function getRepositoryDefinitions() {
		return new RepositoryDefinitions( [
			'' => [
				'database' => false,
				'prefix-mapping' => [],
				'entity-types' => [ 'item', 'property' ],
			],
			'media' => [
				'database' => 'foowiki',
				'prefix-mapping' => [],
				'entity-types' => [ 'mediainfo' ],
			],
			'lexeme' => [
				'database' => 'bazwiki',
				'prefix-mapping' => [ 'foo' => 'media' ],
				'entity-types' => [ 'lexeme' ],
			],
		] );
	}

	public function testGetRepositoryNames() {
		$definitions = $this->getRepositoryDefinitions();

		$this->assertEquals( [ '', 'media', 'lexeme' ], $definitions->getRepositoryNames() );
	}

	public function testGetDatabaseNames() {
		$definitions = $this->getRepositoryDefinitions();

		$this->assertEquals(
			[ '' => false, 'media' => 'foowiki', 'lexeme' => 'bazwiki' ],
			$definitions->getDatabaseNames()
		);
	}

	public function testGetPrefixMappings() {
		$definitions = $this->getRepositoryDefinitions();

		$this->assertEquals(
			[ '' => [], 'media' => [], 'lexeme' => [ 'foo' => 'media' ] ],
			$definitions->getPrefixMappings()
		);
	}

	public function testGetEntityTypeToRepositoryMapping() {
		$definitions = $this->getRepositoryDefinitions();

		$this->assertEquals(
			[ 'item' => '', 'property' => '', 'mediainfo' => 'media', 'lexeme' => 'lexeme' ],
			$definitions->getEntityTypeToRepositoryMapping()
		);
	}

	// TODO: test that if repository definition does not have some field defined, it is skipped
	// in the result of the relevant method

	// TODO: test warning is emitted when multiple repository provide the same entity type

}
