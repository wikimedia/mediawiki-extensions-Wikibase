<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ItemIdRequestValidatingDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DeserializedRequestAdapterTest extends TestCase {

	public function testGetItemId(): void {
		$itemId = new ItemId( 'Q123' );
		$requestAdapter = new DeserializedRequestAdapter( [ ItemIdRequestValidatingDeserializer::DESERIALIZED_VALUE => $itemId ] );
		$this->assertSame(
			$itemId,
			$requestAdapter->getItemId()
		);
	}

	public function testGivenNoItemId_getItemIdThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getItemId();
	}

}
