<?php

namespace Wikibase\Test\Rdf;

use Closure;
use Wikibase\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\NullEntityMentionListener;
use Wikibase\Rdf\NullDedupeBag;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Purtle\RdfWriter;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\DedupeBag;

/**
 * @covers Wikibase\Rdf\ValueSnakRdfBuilderFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Stas Malyshev
 */
class ValueSnakRdfBuilderFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testGetSimpleValueSnakRdfBuilder() {
		$vocab = new RdfVocabulary( RdfBuilderTestData::URI_BASE, RdfBuilderTestData::URI_DATA );
		$writer = new NTriplesRdfWriter();
		$tracker = new NullEntityMentionListener();
		$dedupe = new NullDedupeBag();

		$constructor = $this->newRdfBuilderConstructorCallback( 'simple', $vocab, $writer, $tracker, $dedupe );

		$factory = new ValueSnakRdfBuilderFactory( array( 'PT:test' => $constructor ) );
		$factory->getSimpleValueSnakRdfBuilder( $vocab, $writer, $tracker, $dedupe );
	}

	public function testGetComplexValueSnakRdfBuilder() {
		$vocab = new RdfVocabulary( RdfBuilderTestData::URI_BASE, RdfBuilderTestData::URI_DATA );
		$writer = new NTriplesRdfWriter();
		$tracker = new NullEntityMentionListener();
		$dedupe = new NullDedupeBag();

		$constructor = $this->newRdfBuilderConstructorCallback( 'complex', $vocab, $writer, $tracker, $dedupe );

		$factory = new ValueSnakRdfBuilderFactory( array( 'PT:test' => $constructor ) );
		$factory->getComplexValueSnakRdfBuilder( $vocab, $writer, $tracker, $dedupe );
	}

	/**
	 * Constructs a closure that asserts that it is being called with the expected parameters.
	 *
	 * @param $expectedMode
	 * @param RdfVocabulary $expectedVocab
	 * @param RdfWriter $expectedWriter
	 * @param EntityMentionListener $expectedTracker
	 * @param DedupeBag $expectedDedupe
	 *
	 * @return Closure
	 */
	private function newRdfBuilderConstructorCallback(
		$expectedMode,
		RdfVocabulary $expectedVocab,
		RdfWriter $expectedWriter,
		EntityMentionListener $expectedTracker,
		DedupeBag $expectedDedupe
	) {
		$mockBuilder = $this->getMock( 'Wikibase\Rdf\ValueSnakRdfBuilder' );

		return function(
			$mode,
			RdfVocabulary $vocab,
			RdfWriter $writer,
			EntityMentionListener $tracker,
			DedupeBag $dedupe
		) use(
			$expectedMode,
			$expectedVocab,
			$expectedWriter,
			$expectedTracker,
			$expectedDedupe,
			$mockBuilder
		) {
			\PHPUnit_Framework_Assert::assertSame( $expectedMode, $mode );
			\PHPUnit_Framework_Assert::assertSame( $expectedVocab, $vocab );
			\PHPUnit_Framework_Assert::assertSame( $expectedWriter, $writer );
			\PHPUnit_Framework_Assert::assertSame( $expectedTracker, $tracker );
			\PHPUnit_Framework_Assert::assertSame( $expectedDedupe, $dedupe );

			return $mockBuilder;
		};
	}
}
