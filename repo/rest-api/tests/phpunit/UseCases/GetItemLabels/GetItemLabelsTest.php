<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsSuccessResponse;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemLabelsTest extends TestCase {

	public function testSuccess(): void {
		$labels = new TermList( [
			new Term( 'en', 'earth' ),
			new Term( 'ar', 'أرض' ),
		] );

		$itemId = new ItemId( 'Q10' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$itemRevisionMetadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( $revisionId, $lastModified ) );

		$labelsRetriever = $this->createMock( ItemLabelsRetriever::class );
		$labelsRetriever->expects( $this->once() )
			->method( 'getLabels' )
			->with( $itemId )
			->willReturn( $labels );

		$request = new GetItemLabelsRequest( 'Q10' );
		$response = ( new GetItemLabels( $itemRevisionMetadataRetriever, $labelsRetriever ) )->execute( $request );
		$this->assertEquals( new GetItemLabelsSuccessResponse( $labels, $lastModified, $revisionId ), $response );
	}

}
