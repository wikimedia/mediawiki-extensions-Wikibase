<?php

namespace Wikibase\View\Tests;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\View\DefaultMetaTagsCreator;

/**
 * @covers \Wikibase\View\DefaultMetaTagsCreator
 *
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class DefaultMetaTagsCreatorTest extends EntityMetaTagsCreatorTestCase {

	public static function provideTestGetMetaTags() {

		$defaultMetaTagsCreator = new DefaultMetaTagsCreator();

		yield 'entity meta tags created with Entity that has no id' => [
			fn () => $defaultMetaTagsCreator,
			fn ( self $self ) => $self->createMock( EntityDocument::class ),
			[],
		];

		yield 'entity meta tags created with Entity that has an id' => [
			fn () => $defaultMetaTagsCreator,
			fn ( self $self ) => $self->getMockEntityWithId(),
			[
				'title' => 'EntityID12345',
			],
		];
	}

	private function getMockEntityWithId() {
		$mockEntity = $this->createMock( EntityDocument::class );
		$mockEntity->method( 'getId' )
			->willReturn( $this->getMockEntityId() );
		return $mockEntity;
	}

	private function getMockEntityId() {
		$mockId = $this->createMock( EntityId::class );
		$mockId->method( 'getSerialization' )
			->willReturn( 'EntityID12345' );
		return $mockId;
	}

}
