<?php

namespace Wikibase\Test\Rdf;

use Closure;
use PHPUnit_Framework_TestCase;
use Wikibase\Rdf\ValueSnakRdfBuilder;
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
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ValueSnakRdfBuilderFactoryTest extends PHPUnit_Framework_TestCase {

	public function testGetSimpleValueSnakRdfBuilder() {
		$vocab = new RdfVocabulary( RdfBuilderTestData::URI_BASE, RdfBuilderTestData::URI_DATA );
		$writer = new NTriplesRdfWriter();
		$tracker = new NullEntityMentionListener();
		$dedupe = new NullDedupeBag();
		$called = false;

		$constructor = $this->newRdfBuilderConstructorCallback(
			'simple', $vocab, $writer, $tracker, $dedupe, $called
		);

		$factory = new ValueSnakRdfBuilderFactory( array( 'PT:test' => $constructor ) );
		$factory->getSimpleValueSnakRdfBuilder( $vocab, $writer, $tracker, $dedupe );
		$this->assertTrue( $called );
	}

	public function testGetComplexValueSnakRdfBuilder() {
		$vocab = new RdfVocabulary( RdfBuilderTestData::URI_BASE, RdfBuilderTestData::URI_DATA );
		$writer = new NTriplesRdfWriter();
		$tracker = new NullEntityMentionListener();
		$dedupe = new NullDedupeBag();
		$called = false;

		$constructor = $this->newRdfBuilderConstructorCallback(
			'complex', $vocab, $writer, $tracker, $dedupe, $called
		);

		$factory = new ValueSnakRdfBuilderFactory( array( 'PT:test' => $constructor ) );
		$factory->getComplexValueSnakRdfBuilder( $vocab, $writer, $tracker, $dedupe );
		$this->assertTrue( $called );
	}

	/**
	 * Constructs a closure that asserts that it is being called with the expected parameters.
	 *
	 * @param string $expectedMode
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
		$valueSnakRdfBuilder = $this->getMock( ValueSnakRdfBuilder::class );

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
			$valueSnakRdfBuilder,
			&$called
		) {
			$this->assertSame( $expectedMode, $mode );
			$this->assertSame( $expectedVocab, $vocab );
			$this->assertSame( $expectedWriter, $writer );
			$this->assertSame( $expectedTracker, $tracker );
			$this->assertSame( $expectedDedupe, $dedupe );
			$called = true;

			return $valueSnakRdfBuilder;
		};
	}

}
