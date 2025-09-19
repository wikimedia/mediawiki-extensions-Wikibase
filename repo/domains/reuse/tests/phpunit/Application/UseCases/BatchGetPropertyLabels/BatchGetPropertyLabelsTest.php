<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabelsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabels
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BatchGetPropertyLabelsTest extends TestCase {

	private InMemoryPrefetchingTermLookup $lookup;

	public function setUp(): void {
		$enDeLabelProperty = new Property(
			new NumericPropertyId( 'P1' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'instance of' ), new Term( 'de', 'ist ein(e)' ) ] ) ),
			'string'
		);
		$enLabelProperty = new Property(
			new NumericPropertyId( 'P2' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'subclass of' ) ] ) ),
			'string'
		);
		$deLabelProperty = new Property(
			new NumericPropertyId( 'P3' ),
			new Fingerprint( new TermList( [ new Term( 'de', 'befindet sich in' ) ] ) ),
			'string'
		);

		$this->lookup = new InMemoryPrefetchingTermLookup();
		$this->lookup->setData( [ $enDeLabelProperty, $enLabelProperty, $deLabelProperty ] );
	}

	public function testGetPropertyLabelsBatch(): void {
		$expectedLabelsBatch = new PropertyLabelsBatch( [
			'P1' => new Labels( new Label( 'en', 'instance of' ), new Label( 'de', 'ist ein(e)' ) ),
			'P2' => new Labels( new Label( 'en', 'subclass of' ) ),
			'P3' => new Labels( new Label( 'de', 'befindet sich in' ) ),
		] );

		$this->assertEquals(
			$expectedLabelsBatch,
			$this->newUseCase()->execute(
				new BatchGetPropertyLabelsRequest( [ 'P1', 'P2', 'P3' ], [ 'de', 'en' ] )
			)->batch
		);
	}

	private function newUseCase(): BatchGetPropertyLabels {
		return new BatchGetPropertyLabels(
			new PrefetchingTermLookupBatchLabelsRetriever( $this->lookup )
		);
	}
}
