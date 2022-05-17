<?php

namespace Wikibase\Repo\Tests\Rdf;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\FullStatementRdfBuilder;
use Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\ItemRdfBuilder;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\SiteLinksRdfBuilder;
use Wikibase\Repo\Rdf\TermsRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\ItemRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class ItemRdfBuilderTest extends TestCase {

	private $dedupe;
	private $vocabulary;
	private $writer;
	private $mentionedEntityTracker;
	private $siteLinksRdfBuilder;
	private $valueSnakRdfBuilderFactory;
	private $termsRdfBuilder;
	private $truthyStatementRdfBuilderFactory;
	private $fullStatementRdfBuilderFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->dedupe = $this->createMock( DedupeBag::class );
		$this->vocabulary = $this->createMock( RdfVocabulary::class );
		$this->writer = $this->createMock( RdfWriter::class );
		$this->mentionedEntityTracker = $this->createMock( EntityMentionListener::class );
		$this->siteLinksRdfBuilder = $this->createMock( SiteLinksRdfBuilder::class );
		$this->valueSnakRdfBuilderFactory = $this->createMock( ValueSnakRdfBuilderFactory::class );
		$this->termsRdfBuilder = $this->createMock( TermsRdfBuilder::class );
		$this->truthyStatementRdfBuilderFactory = $this->createMock( TruthyStatementRdfBuilderFactory::class );
		$this->fullStatementRdfBuilderFactory = $this->createMock( FullStatementRdfBuilderFactory::class );
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testInternalRdfBuildersCallsAddEntity_dependingOnFlavorFlags(
		int $flavorFlags,
		Item $item,
		bool $expectSitelinkBuilderCalled = false,
		bool $expectTruthyBuilderCalled = false,
		bool $expectFullBuilderCalled = false
	): void {
		$this->termsRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $item );
		if ( $expectSitelinkBuilderCalled ) {
			$this->siteLinksRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $item );
		} else {
			$this->siteLinksRdfBuilder->expects( $this->never() )->method( 'addEntity' );
		}
		$truthyStatementRdfBuilder = $this->createMock( TruthyStatementRdfBuilder::class );
		$this->truthyStatementRdfBuilderFactory->method( 'getTruthyStatementRdfBuilder' )->willReturn( $truthyStatementRdfBuilder );
		if ( $expectTruthyBuilderCalled ) {
			$truthyStatementRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $item );
		} else {
			$truthyStatementRdfBuilder->expects( $this->never() )->method( 'addEntity' );
		}
		$fullStatementRdfBuilder = $this->createMock( FullStatementRdfBuilder::class );
		$this->fullStatementRdfBuilderFactory->method( 'getFullStatementRdfBuilder' )->willReturn( $fullStatementRdfBuilder );
		if ( $expectFullBuilderCalled ) {
			$fullStatementRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $item );
		} else {
			$fullStatementRdfBuilder->expects( $this->never() )->method( 'addEntity' );
		}
		$builder = $this->getBuilder( $flavorFlags );
		$builder->addEntity( $item );
	}

	public function provideAddEntity(): array {
		return [
			"No flavors selected" => [ 0, new Item() ],
			"Just sitelinks requested" => [ RdfProducer::PRODUCE_SITELINKS, new Item(), true ],
			"Just truthy statements requested" => [ RdfProducer::PRODUCE_TRUTHY_STATEMENTS, new Item(), false, true ],
			"Full statements requested" => [ RdfProducer::PRODUCE_ALL_STATEMENTS, new Item(), false, false, true ],
			"All statements requested" => [ RdfProducer::PRODUCE_ALL, new Item(), true, true, true ],
		];
	}

	private function getBuilder( $flavorFlags ): ItemRdfBuilder {
		return new ItemRdfBuilder(
			$flavorFlags,
			$this->siteLinksRdfBuilder,
			$this->termsRdfBuilder,
			$this->truthyStatementRdfBuilderFactory,
			$this->fullStatementRdfBuilderFactory
		);
	}

}
