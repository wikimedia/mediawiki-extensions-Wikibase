<?php

namespace Wikibase\Repo\Tests\Rdf;

use Closure;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\EntityRdfBuilder;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\NullDedupeBag;
use Wikibase\Repo\Rdf\NullEntityMentionListener;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\EntityRdfBuilderFactory
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class EntityRdfBuilderFactoryTest extends \PHPUnit\Framework\TestCase {

	public function provideBuilderFlags() {
		return [
			[ 0 ], // simple values
			[ RdfProducer::PRODUCE_FULL_VALUES ], // complex values
		];
	}

	/**
	 * @dataProvider provideBuilderFlags
	 */
	public function testGetEntityRdfBuilder( $flags ) {
		$vocab = new RdfVocabulary(
			[ '' => RdfBuilderTestData::URI_BASE ],
			[ '' => RdfBuilderTestData::URI_DATA ],
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			[ '' => '' ],
			[ '' => '' ]
		);
		$writer = new NTriplesRdfWriter();
		$tracker = new NullEntityMentionListener();
		$dedupe = new NullDedupeBag();
		$called = false;

		$constructor = $this->newRdfBuilderConstructorCallback(
			$flags, $vocab, $writer, $tracker, $dedupe, $called
		);

		$factory = new EntityRdfBuilderFactory( [ 'test' => $constructor ] );
		$rdfBuilder = $factory->getEntityRdfBuilders( $flags, $vocab, $writer, $tracker, $dedupe );
		$this->assertTrue( $called );
		$this->assertInstanceOf( EntityRdfBuilder::class, $rdfBuilder['test'] );
	}

	/**
	 * Constructs a closure that asserts that it is being called with the expected parameters.
	 *
	 * @param int $expectedMode
	 * @param RdfVocabulary $expectedVocab
	 * @param RdfWriter $expectedWriter
	 * @param EntityMentionListener $expectedTracker
	 * @param DedupeBag $expectedDedupe
	 * @param bool &$called Will be set to true once the returned function has been called.
	 *
	 * @return Closure
	 */
	private function newRdfBuilderConstructorCallback(
		$expectedMode,
		RdfVocabulary $expectedVocab,
		RdfWriter $expectedWriter,
		EntityMentionListener $expectedTracker,
		DedupeBag $expectedDedupe,
		&$called
	) {
		$entityRdfBuilder = $this->createMock( EntityRdfBuilder::class );

		return function(
			$mode,
			RdfVocabulary $vocab,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) use (
			$expectedMode,
			$expectedVocab,
			$expectedWriter,
			$expectedTracker,
			$expectedDedupe,
			$entityRdfBuilder,
			&$called
		) {
			$this->assertSame( $expectedMode, $mode );
			$this->assertSame( $expectedVocab, $vocab );
			$this->assertSame( $expectedWriter, $writer );
			$this->assertSame( $expectedTracker, $tracker );
			$this->assertSame( $expectedDedupe, $dedupe );
			$called = true;

			return $entityRdfBuilder;
		};
	}

}
