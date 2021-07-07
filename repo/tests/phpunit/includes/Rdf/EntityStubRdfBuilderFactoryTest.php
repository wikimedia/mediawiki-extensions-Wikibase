<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Rdf;

use Closure;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Rdf\EntityStubRdfBuilder;
use Wikibase\Repo\Rdf\EntityStubRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\EntityStubRdfBuilderFactory
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class EntityStubRdfBuilderFactoryTest extends TestCase {

	public function testGetEntityRdfBuilder(): void {
		$vocab = new RdfVocabulary(
			[ '' => RdfBuilderTestData::URI_BASE ],
			[ '' => RdfBuilderTestData::URI_DATA ],
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			[ '' => '' ],
			[ '' => '' ]
		);
		$writer = new NTriplesRdfWriter();
		$called = false;
		$constructor = $this->newRdfBuilderConstructorCallback( $vocab, $writer, $called );

		$factory = new EntityStubRdfBuilderFactory( [ 'test' => $constructor ] );
		$rdfBuilder = $factory->getEntityStubRdfBuilders( $vocab, $writer );
		$this->assertTrue( $called );
		$this->assertInstanceOf( EntityStubRdfBuilder::class, $rdfBuilder['test'] );
	}

	/**
	 * Constructs a closure that asserts that it is being called with the expected parameters.
	 *
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 * @param bool &$called Will be set to true once the returned function has been called.
	 *
	 * @return Closure
	 */
	private function newRdfBuilderConstructorCallback(
		RdfVocabulary $expectedVocabulary,
		RdfWriter $expectedWriter,
		&$called
	): callable {
		return function(
			RdfVocabulary $vocabulary,
			RdfWriter $writer
		) use (
			$expectedVocabulary,
			$expectedWriter,
			&$called
		) {
			$entityStubRdfBuilder = $this->createMock( EntityStubRdfBuilder::class );
			$this->assertSame( $expectedVocabulary, $vocabulary );
			$this->assertSame( $expectedWriter, $writer );
			$called = true;

			return $entityStubRdfBuilder;
		};
	}

}
