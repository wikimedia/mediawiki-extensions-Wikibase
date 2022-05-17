<?php

namespace Wikibase\Repo\Tests\Rdf;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\DispatchingValueSnakRdfBuilder;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class TruthyStatementRdfBuilderFactoryTest extends TestCase {

	private $dedupe;
	private $vocabulary;
	private $writer;
	private $mentionedEntityTracker;
	private $valueSnakRdfBuilderFactory;
	private $propertyDataLookup;

	protected function setUp(): void {
		parent::setUp();
		$this->dedupe = $this->createMock( DedupeBag::class );
		$this->vocabulary = $this->createMock( RdfVocabulary::class );
		$this->writer = $this->createMock( RdfWriter::class );
		$this->mentionedEntityTracker = $this->createMock( EntityMentionListener::class );
		$this->valueSnakRdfBuilderFactory = $this->createMock( ValueSnakRdfBuilderFactory::class );
		$this->valueSnakRdfBuilder = $this->createMock( DispatchingValueSnakRdfBuilder::class );
		$this->valueSnakRdfBuilderFactory->method( 'getValueSnakRdfBuilder' )->willReturn( $this->valueSnakRdfBuilder );
		$this->propertyDataLookup = $this->createMock( PropertyDataTypeLookup::class );
	}

	public function testGetTruthyStatementRdfBuilder(): void {
		$factory = $this->getFactory();
		$builder = $factory->getTruthyStatementRdfBuilder( 0 );
		$this->assertInstanceOf( TruthyStatementRdfBuilder::class, $builder );
	}

	private function getFactory(): TruthyStatementRdfBuilderFactory {
		return new TruthyStatementRdfBuilderFactory(
			$this->dedupe,
			$this->vocabulary,
			$this->writer,
			$this->valueSnakRdfBuilderFactory,
			$this->mentionedEntityTracker,
			$this->propertyDataLookup
		);
	}

}
