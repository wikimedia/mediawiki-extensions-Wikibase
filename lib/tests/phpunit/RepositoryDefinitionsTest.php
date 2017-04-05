<?php

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use Wikibase\Lib\RepositoryDefinitions;

/**
 * @covers Wikibase\Lib\RepositoryDefinitions
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class RepositoryDefinitionsTest extends \PHPUnit_Framework_TestCase {

	public function provideInvalidConstructorArguments() {
		return [
			'repository name containing colon' => [ [ 'fo:o' => [] ] ],
			'repository definition not an array' => [ [ '' => 'string' ] ],
			'no database key in repository definition' => [
				[ '' => [
					'base-concept-uri' => 'http://acme.test/concept/',
					'base-data-uri' => 'http://acme.test/data/',
					'entity-types' => [],
					'prefix-mapping' => []
				] ]
			],
			'no entity-types key in repository definition' => [
				[ '' => [
					'database' => 'xyz',
					'base-concept-uri' => 'http://acme.test/concept/',
					'base-data-uri' => 'http://acme.test/data/',
					'prefix-mapping' => []
				] ]
			],
			'no prefix-mapping key in repository definition' => [
				[ '' => [
					'database' => 'xyz',
					'base-concept-uri' => 'http://acme.test/concept/',
					'base-data-uri' => 'http://acme.test/data/',
					'entity-types' => []
				] ]
			],
			'no base-concept-uri key in repository definition' => [
				[ '' => [
					'database' => 'xyz',
					'base-data-uri' => 'http://acme.test/data/',
					'entity-types' => [],
					'prefix-mapping' => []
				] ]
			],
			'no base-data-uri key in repository definition' => [
				[ '' => [
					'database' => 'xyz',
					'base-concept-uri' => 'http://acme.test/concept/',
					'entity-types' => [],
					'prefix-mapping' => []
				] ]
			],
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

	/**
	 * @return array
	 */
	private function getCompleteRepositoryDefinitionArray() {
		return [
			'' => [
				'database' => false,
				'base-concept-uri' => 'http://acme.test/concept/',
				'base-data-uri' => 'http://acme.test/data/',
				'entity-types' => [ 'item', 'property' ],
				'prefix-mapping' => [],
			],
			'media' => [
				'database' => 'foowiki',
				'base-concept-uri' => 'http://foo.test/concept/',
				'base-data-uri' => 'http://foo.test/data/',
				'entity-types' => [ 'mediainfo' ],
				'prefix-mapping' => [],
			],
			'lexeme' => [
				'database' => 'bazwiki',
				'base-concept-uri' => 'http://baz.test/concept/',
				'base-data-uri' => 'http://baz.test/data/',
				'entity-types' => [ 'lexeme' ],
				'prefix-mapping' => [ 'foo' => 'media' ],
			],
		];
	}

	public function testGetRepositoryNames() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray() );

		$this->assertEquals( [ '', 'media', 'lexeme' ], $definitions->getRepositoryNames() );
	}

	public function testGetDatabaseNames() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray() );

		$this->assertEquals(
			[ '' => false, 'media' => 'foowiki', 'lexeme' => 'bazwiki' ],
			$definitions->getDatabaseNames()
		);
	}

	public function testGetConceptBaseUris() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray() );

		$this->assertEquals(
			[
				'' => 'http://acme.test/concept/',
				'media' => 'http://foo.test/concept/',
				'lexeme' => 'http://baz.test/concept/'
			],
			$definitions->getConceptBaseUris()
		);
	}

	public function testGetDataBaseUris() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray() );

		$this->assertEquals(
			[
				'' => 'http://acme.test/data/',
				'media' => 'http://foo.test/data/',
				'lexeme' => 'http://baz.test/data/'
			],
			$definitions->getDataBaseUris()
		);
	}

	public function testGetPrefixMappings() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray() );

		$this->assertEquals(
			[ '' => [], 'media' => [], 'lexeme' => [ 'foo' => 'media' ] ],
			$definitions->getPrefixMappings()
		);
	}

	public function testGetEntityTypesPerRepository() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray() );

		$this->assertEquals(
			[
				'' => [ 'item', 'property' ],
				'media' => [ 'mediainfo' ],
				'lexeme' => [ 'lexeme' ],
			],
			$definitions->getEntityTypesPerRepository()
		);
	}

	public function testGetEntityTypeToRepositoryMapping() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray() );

		$this->assertEquals(
			[ 'item' => '', 'property' => '', 'mediainfo' => 'media', 'lexeme' => 'lexeme' ],
			$definitions->getEntityTypeToRepositoryMapping()
		);
	}

	public function testGivenSameEntityTypeDefinedForMultitpleRepos_exceptionIsThrown() {
		$this->setExpectedException( InvalidArgumentException::class );

		$irrelevantDefinitions = [
			'database' => 'foo',
			'base-concept-uri' => 'http://acme.test/concept/',
			'base-data-uri' => 'http://acme.test/data/',
			'prefix-mapping' => []
		];

		new RepositoryDefinitions( [
			'' => array_merge( $irrelevantDefinitions, [ 'entity-types' => [ 'item', 'property' ] ] ),
			'media' => array_merge( $irrelevantDefinitions, [ 'entity-types' => [ 'item', 'mediainfo' ] ] ),
		] );
	}

	public function testGetAllEntityTypes() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray() );

		$this->assertEquals(
			[ 'item', 'property', 'mediainfo', 'lexeme' ],
			$definitions->getAllEntityTypes()
		);
	}

}
