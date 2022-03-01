<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemRetriever;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItem\GetItem
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class GetItemIntegrationTest extends MediaWikiIntegrationTestCase {

	public function testGetExistingItem(): void {
		$entityStore = WikibaseRepo::getEntityStore();
		$itemLabel = "potato";

		$item = NewItem::withLabel( "en", $itemLabel )->build();
		$entityStore->saveEntity( $item, self::class, self::getTestUser()->getUser(), EDIT_NEW );

		$useCase = new GetItem(
			new WikibaseEntityLookupItemRetriever(
				WikibaseRepo::getEntityLookup()
			),
			new ItemSerializer( WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer() )
		);

		$itemResult = $useCase->execute( new GetItemRequest( $item->getId()->getSerialization() ) );

		$this->assertSame(
			$item->getId()->getSerialization(),
			$itemResult->getItem()['id']
		);
		$this->assertSame(
			$itemLabel,
			$itemResult->getItem()['labels']['en']['value']
		);
	}

}
