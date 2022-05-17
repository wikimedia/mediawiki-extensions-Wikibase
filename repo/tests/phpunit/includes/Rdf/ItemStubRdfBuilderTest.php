<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Rdf;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Repo\Rdf\ItemStubRdfBuilder;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\ItemStubRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class ItemStubRdfBuilderTest extends TestCase {

	private $writer;
	private $vocab;
	private $termLookup;
	private $languages;

	protected function setUp(): void {
		$this->termLookup = $this->createMock( PrefetchingItemTermLookup::class );
		$this->vocab = $this->createMock( RdfVocabulary::class );
		$this->writer = $this->createMock( RdfWriter::class );
		$this->languages = [ 'pl', 'en' ];
		$this->vocab->entityNamespaceNames[''] = 'cat';
		$this->termLookup->method( 'getLabels' )->willReturn( [] );
		$this->termLookup->method( 'getDescriptions' )->willReturn( [] );
	}

	private function getBuilder(): ItemStubRdfBuilder {
		return new ItemStubRdfBuilder(
			$this->termLookup,
			$this->vocab,
			$this->writer,
			[],
			$this->languages
		);
	}

	public function testAddEntityStub() {
		$itemId = new ItemId( 'Q15436' );
		$this->termLookup->expects( $this->atLeastOnce() )->method( 'getLabels' )->with( $itemId, $this->languages );
		$this->termLookup->expects( $this->atLeastOnce() )->method( 'getDescriptions' )->with( $itemId, $this->languages );

		$builder = $this->getBuilder();
		$builder->addEntityStub( $itemId );
	}

	public function testPrefetchingHappens() {
		$itemId = new ItemId( 'Q15436' );
		$this->termLookup->expects( $this->atLeastOnce() )
			->method( 'getLabels' )
			->with( $itemId, $this->languages );
		$this->termLookup->expects( $this->atLeastOnce() )
			->method( 'getDescriptions' )
			->with( $itemId, $this->languages );
		$this->termLookup->expects( $this->atLeastOnce() )
			->method( 'prefetchTerms' )
			->with(
				[ $itemId ],
				[ TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_LABEL ],
				$this->languages
			);

		$builder = $this->getBuilder();
		$builder->markForPrefetchingEntityStub( $itemId );
		$builder->addEntityStub( $itemId );
	}

}
