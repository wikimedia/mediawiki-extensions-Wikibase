<?php

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\RepositoryDefinitions;

/**
 * @covers \Wikibase\Lib\RepositoryDefinitions
 *
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 */
class RepositoryDefinitionsTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function provideInvalidConstructorArguments() {
		return [
			'repository name containing colon' => [ [ 'fo:o' => [] ] ],
			'repository definition not an array' => [ [ '' => 'string' ] ],
			'no database key in repository definition' => [
				[ '' => [ 'base-uri' => 'http://acme.test/concept/' ], 'entity-namespaces' => [], 'prefix-mapping' => [] ]
			],
			'no entity-namespaces key in repository definition' => [
				[ '' => [ 'database' => 'xyz', 'base-uri' => 'http://acme.test/concept/' ], 'prefix-mapping' => [] ]
			],
			'no prefix-mapping key in repository definition' => [
				[ '' => [ 'database' => 'xyz', 'base-uri' => 'http://acme.test/concept/' ], 'entity-namespaces' => [] ]
			],
			'no base-uri key in repository definition' => [
				[ '' => [ 'database' => 'xyz', 'entity-namespaces' => [], 'prefix-mapping' => [] ] ]
			],
			'no settings for the local repository' => [ [ 'foo' => [ 'database' => 'foodb' ] ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidArguments_constructorThrowsException( array $args ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new RepositoryDefinitions( $args, $this->getEntityTypeDefinitions() );
	}

	/**
	 * @return array
	 */
	private function getCompleteRepositoryDefinitionArray() {
		return [
			'' => [
				'database' => false,
				'base-uri' => 'http://acme.test/concept/',
				'entity-namespaces' => [ 'item' => 666, 'property' => 777 ],
				'prefix-mapping' => [],
			],
			'media' => [
				'database' => 'foowiki',
				'base-uri' => 'http://foo.test/concept/',
				'entity-namespaces' => [
					'mediainfo' => NS_FILE . '/mediainfo',
					'galleryinfo' => '/galleryinfo',
					'userinfo' => 'User/userinfo',
				],
				'prefix-mapping' => [],
			],
			'lexeme' => [
				'database' => 'bazwiki',
				'base-uri' => 'http://baz.test/concept/',
				'entity-namespaces' => [ 'lexeme' => 999 ],
				'prefix-mapping' => [ 'foo' => 'media' ],
			],
		];
	}

	public function testGetRepositoryNames() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray(), $this->getEntityTypeDefinitions() );

		$this->assertEquals( [ '', 'media', 'lexeme' ], $definitions->getRepositoryNames() );
	}

	public function testGetDatabaseNames() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray(), $this->getEntityTypeDefinitions() );

		$this->assertEquals(
			[ '' => false, 'media' => 'foowiki', 'lexeme' => 'bazwiki' ],
			$definitions->getDatabaseNames()
		);
	}

	public function testGetConceptBaseUris() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray(), $this->getEntityTypeDefinitions() );

		$this->assertEquals(
			[
				'' => 'http://acme.test/concept/',
				'media' => 'http://foo.test/concept/',
				'lexeme' => 'http://baz.test/concept/'
			],
			$definitions->getConceptBaseUris()
		);
	}

	public function testGetPrefixMappings() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray(), $this->getEntityTypeDefinitions() );

		$this->assertEquals(
			[ '' => [], 'media' => [], 'lexeme' => [ 'foo' => 'media' ] ],
			$definitions->getPrefixMappings()
		);
	}

	public function testGetEntityTypesPerRepository() {
		$definitions = new RepositoryDefinitions(
			$this->getCompleteRepositoryDefinitionArray(),
			$this->getEntityTypeDefinitions()
		);

		$this->assertEquals(
			[
				'' => [ 'item', 'property' ],
				'media' => [ 'mediainfo', 'galleryinfo', 'userinfo' ],
				'lexeme' => [ 'lexeme', 'form' ],
			],
			$definitions->getEntityTypesPerRepository()
		);
	}

	public function testGetEntityTypeToRepositoryMapping() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray(), $this->getEntityTypeDefinitions() );

		$this->assertEquals(
			[
				'item' => [ [ '', 666, 'main' ] ],
				'property' => [ [ '', 777, 'main' ] ],
				'mediainfo' => [ [ 'media', NS_FILE, 'mediainfo' ] ],
				'galleryinfo' => [ [ 'media', NS_MAIN, 'galleryinfo' ] ],
				'userinfo' => [ [ 'media', NS_USER, 'userinfo' ] ],
				'lexeme' => [ [ 'lexeme', 999, 'main' ] ]
			],
			$definitions->getEntityTypeToRepositoryMapping()
		);
	}

	public function testGivenSameEntityTypeDefinedForMultitpleRepos_exceptionIsThrown() {
		$this->setExpectedException( InvalidArgumentException::class );

		$irrelevantDefinitions = [ 'database' => 'foo', 'base-uri' => 'http://acme.test/concept/', 'prefix-mapping' => [] ];

		new RepositoryDefinitions(
			[
				'' => array_merge( $irrelevantDefinitions, [ 'entity-namespaces' => [ 'item' => 666, 'property' => 777 ] ] ),
				'media' => array_merge( $irrelevantDefinitions, [ 'entity-namespaces' => [ 'item' => 111, 'mediainfo' => 222 ] ] ),
			],
			$this->getEntityTypeDefinitions()
		);
	}

	public function testGetAllEntityTypes() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray(), $this->getEntityTypeDefinitions() );

		$this->assertEquals(
			[ 'item', 'property', 'mediainfo', 'galleryinfo', 'userinfo', 'lexeme', 'form' ],
			$definitions->getAllEntityTypes()
		);
	}

	public function testGetEntityNamespaces() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray(), $this->getEntityTypeDefinitions() );

		$this->assertEquals(
			[
				'item' => 666,
				'property' => 777,
				'mediainfo' => NS_FILE,
				'galleryinfo' => NS_MAIN,
				'userinfo' => NS_USER,
				'lexeme' => 999
			],
			$definitions->getEntityNamespaces()
		);
	}

	public function testGetEntitySlots() {
		$definitions = new RepositoryDefinitions( $this->getCompleteRepositoryDefinitionArray(), $this->getEntityTypeDefinitions() );

		$this->assertEquals(
			[
				'item' => 'main',
				'property' => 'main',
				'mediainfo' => 'mediainfo',
				'galleryinfo' => 'galleryinfo',
				'userinfo' => 'userinfo',
				'lexeme' => 'main'
			],
			$definitions->getEntitySlots()
		);
	}

	private function getEntityTypeDefinitions() {
		return new EntityTypeDefinitions(
			[
				'lexeme' => [
					'sub-entity-types' => [
						'form',
					],
				],
			]
		);
	}

	/**
	 * Test specifically for https://phabricator.wikimedia.org/T208308
	 * When a repository is defined with no namespaces / entity types then everything
	 * should still work.
	 */
	public function testGetEntityTypesPerRepository_returnsArrayWhenNoEntities() {
		$etd = new RepositoryDefinitions(
			[
				'' => [
					'database' => false,
					'base-uri' => 'http://acme.test/concept/',
					'entity-namespaces' => [],
					'prefix-mapping' => [],
				],
			],
			$this->getEntityTypeDefinitions()
		);
		$this->assertEquals( [ '' => [] ], $etd->getEntityTypesPerRepository() );
	}

}
