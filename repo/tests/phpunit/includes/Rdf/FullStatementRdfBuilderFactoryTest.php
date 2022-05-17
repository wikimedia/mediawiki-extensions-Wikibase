<?php

namespace Wikibase\Repo\Tests\Rdf;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\DispatchingValueSnakRdfBuilder;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\FullStatementRdfBuilder;
use Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class FullStatementRdfBuilderFactoryTest extends TestCase {

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

	public function testGetFullStatementRdfBuilder(): void {
		$factory = $this->getFactory();
		$builder = $factory->getFullStatementRdfBuilder( 0 );
		$this->assertInstanceOf( FullStatementRdfBuilder::class, $builder );
	}

	private function getFactory(): FullStatementRdfBuilderFactory {
		return new FullStatementRdfBuilderFactory(
			$this->vocabulary,
			$this->writer,
			$this->valueSnakRdfBuilderFactory,
			$this->mentionedEntityTracker,
			$this->dedupe,
			$this->propertyDataLookup
		);
	}

}
