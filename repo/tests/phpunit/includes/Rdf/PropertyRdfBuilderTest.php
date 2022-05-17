<?php

namespace Wikibase\Repo\Tests\Rdf;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Rdf\FullStatementRdfBuilder;
use Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\PropertyRdfBuilder;
use Wikibase\Repo\Rdf\PropertySpecificComponentsRdfBuilder;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\SiteLinksRdfBuilder;
use Wikibase\Repo\Rdf\TermsRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory;

/**
 * @covers \Wikibase\Repo\Rdf\ItemRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class PropertyRdfBuilderTest extends TestCase {

	private $siteLinksRdfBuilder;
	private $termsRdfBuilder;
	private $truthyStatementRdfBuilderFactory;
	private $fullStatementRdfBuilderFactory;
	private $propertySpecificComponentsRdfBuilder;

	protected function setUp(): void {
		parent::setUp();
		$this->siteLinksRdfBuilder = $this->createMock( SiteLinksRdfBuilder::class );
		$this->termsRdfBuilder = $this->createMock( TermsRdfBuilder::class );
		$this->truthyStatementRdfBuilderFactory = $this->createMock( TruthyStatementRdfBuilderFactory::class );
		$this->fullStatementRdfBuilderFactory = $this->createMock( FullStatementRdfBuilderFactory::class );
		$this->propertySpecificComponentsRdfBuilder = $this->createMock( PropertySpecificComponentsRdfBuilder::class );
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testInternalRdfBuildersCallsAddEntity_dependingOnFlavorFlags(
		int $flavorFlags,
		Property $property,
		bool $expectTruthyBuilderCalled = false,
		bool $expectFullBuilderCalled = false
	): void {
		$this->termsRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $property );
		$this->propertySpecificComponentsRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $property );
		$truthyStatementRdfBuilder = $this->createMock( TruthyStatementRdfBuilder::class );
		$this->truthyStatementRdfBuilderFactory->method( 'getTruthyStatementRdfBuilder' )->willReturn( $truthyStatementRdfBuilder );
		if ( $expectTruthyBuilderCalled ) {
			$truthyStatementRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $property );
		} else {
			$truthyStatementRdfBuilder->expects( $this->never() )->method( 'addEntity' );
		}
		$fullStatementRdfBuilder = $this->createMock( FullStatementRdfBuilder::class );
		$this->fullStatementRdfBuilderFactory->method( 'getFullStatementRdfBuilder' )->willReturn( $fullStatementRdfBuilder );
		if ( $expectFullBuilderCalled ) {
			$fullStatementRdfBuilder->expects( $this->atLeastOnce() )->method( 'addEntity' )->with( $property );
		} else {
			$fullStatementRdfBuilder->expects( $this->never() )->method( 'addEntity' );
		}
		$builder = $this->getBuilder( $flavorFlags );
		$builder->addEntity( $property );
	}

	public function provideAddEntity(): array {
		return [
			"No flavors selected" => [ 0, $this->getTestProperty() ],
			"Just truthy statements requested" => [ RdfProducer::PRODUCE_TRUTHY_STATEMENTS, $this->getTestProperty(), true ],
			"Full statements requested" => [ RdfProducer::PRODUCE_ALL_STATEMENTS, $this->getTestProperty(), false, true ],
			"All statements requested" => [ RdfProducer::PRODUCE_ALL, $this->getTestProperty(), true, true ],
		];
	}

	private function getBuilder( $flavorFlags ): PropertyRdfBuilder {
		return new PropertyRdfBuilder(
			$flavorFlags,
			$this->truthyStatementRdfBuilderFactory,
			$this->fullStatementRdfBuilderFactory,
			$this->termsRdfBuilder,
			$this->propertySpecificComponentsRdfBuilder
		);
	}

	private function getTestProperty(): Property {
		return new Property( null,
			null,
			'footype' );
	}

}
