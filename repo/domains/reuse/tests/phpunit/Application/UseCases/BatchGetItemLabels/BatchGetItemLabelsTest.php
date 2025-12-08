<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Application\UseCases\BatchGetItemLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabelsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsDescriptionsRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsDescriptionsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BatchGetItemLabelsTest extends TestCase {

	private InMemoryPrefetchingTermLookup $lookup;

	public function setUp(): void {
		$enArLabelItem = new Item(
			new ItemId( 'Q1' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'Potato' ), new Term( 'ar', 'بطاطا' ) ] ) ),
		);
		$enLabelItem = new Item(
			new ItemId( 'Q2' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'Apple' ) ] ) ),
		);
		$arLabelItem = new Item(
			new ItemId( 'Q3' ),
			new Fingerprint( new TermList( [ new Term( 'ar', 'خبز' ) ] ) ),
		);

		$this->lookup = new InMemoryPrefetchingTermLookup();
		$this->lookup->setData( [ $enArLabelItem, $enLabelItem, $arLabelItem ] );
	}

	public function testGetItemLabelsBatch(): void {
		$expectedLabelsBatch = new ItemLabelsBatch( [
			'Q1' => new Labels( new Label( 'en', 'Potato' ), new Label( 'ar', 'بطاطا' ) ),
			'Q2' => new Labels( new Label( 'en', 'Apple' ) ),
			'Q3' => new Labels( new Label( 'ar', 'خبز' ) ),
		] );

		$this->assertEquals(
			$expectedLabelsBatch,
			$this->newUseCase()->execute(
				new BatchGetItemLabelsRequest( [ 'Q1', 'Q2', 'Q3' ], [ 'ar', 'en' ] )
			)->batch
		);
	}

	private function newUseCase(): BatchGetItemLabels {
		return new BatchGetItemLabels(
			new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( $this->lookup )
		);
	}

}
