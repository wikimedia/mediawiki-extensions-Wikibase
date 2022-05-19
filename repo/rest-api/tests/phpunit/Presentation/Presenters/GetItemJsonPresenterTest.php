<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\SiteLinkListSerializer;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;
use Wikibase\Repo\RestApi\Domain\Model\ItemDataBuilder;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemDataSerializer;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\GetItemJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemJsonPresenterTest extends TestCase {

	public function testGetJson(): void {
		$itemData = $this->createStub( ItemData::class );
		$serialization = [ 'some' => 'serialization' ];

		$serializer = $this->createMock( ItemDataSerializer::class );
		$serializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $itemData )
			->willReturn( $serialization );

		$presenter = new GetItemJsonPresenter( $serializer );

		$this->assertJsonStringEqualsJsonString(
			json_encode( $serialization ),
			$presenter->getJson(
				new GetItemSuccessResponse( $itemData, '20220307180000', 321 )
			)
		);
	}

	public function testEmptyFieldsUseObjects(): void {
		$itemData = ( new ItemDataBuilder() )
			->setId( new ItemId( 'Q1' ) )
			->setLabels( new TermList() )
			->setDescriptions( new TermList() )
			->setAliases( new AliasGroupList() )
			// skipping statements and sitelinks here since serializing these accordingly is the job of the inner serializers.
			->build();

		$presenter = new GetItemJsonPresenter( new ItemDataSerializer(
			$this->createStub( StatementListSerializer::class ),
			$this->createStub( SiteLinkListSerializer::class )
		) );

		$this->assertJsonStringEqualsJsonString(
			'{"id":"Q1","labels":{},"descriptions":{},"aliases":{}}',
			$presenter->getJson( new GetItemSuccessResponse( $itemData, '20220307180000', 321 ) )
		);
	}

}
