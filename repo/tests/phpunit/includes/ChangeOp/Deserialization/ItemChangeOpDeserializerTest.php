<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ItemChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;

/**
 * @covers Wikibase\Repo\ChangeOpDeserializers\ItemChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class ItemChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenEmptyArray_returnsEmptyChangeOps() {
		$this->assertEmpty(
			( new ItemChangeOpDeserializer( $this->getChangeOpDeserializerFactoryMock() ) )
				->createEntityChangeOp( [] )
				->getChangeOps()
		);
	}

	/**
	 * @dataProvider changeRequestProvider
	 */
	public function testGivenChangeRequestContainsFieldKey_callsCorrespondingFactoryMethods( array $changeRequest, $expectedMethods ) {
		$factory = $this->getChangeOpDeserializerFactoryMock();

		foreach ( $expectedMethods as $method ) {
			$factory->expects( $this->once() )
				->method( $method )
				->willReturn( $this->getMockChangeOpDeserializer() );
		}

		/** @var $factory ChangeOpDeserializerFactory */
		( new ItemChangeOpDeserializer( $factory ) )->createEntityChangeOp( $changeRequest );
	}

	public function changeRequestProvider() {
		return [
			[
				[ 'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ],
				[ 'getLabelsChangeOpDeserializer' ],
			],
			[
				[ 'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ],
				[ 'getDescriptionsChangeOpDeserializer' ],
			],
			[
				[ 'aliases' => [ 'de' => [ 'language' => 'de', 'value' => 'bar' ] ] ],
				[ 'getAliasesChangeOpDeserializer' ],
			],
			[
				[
					'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ],
					'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ],
				],
				[ 'getLabelsChangeOpDeserializer', 'getDescriptionsChangeOpDeserializer' ],
			],
			[
				[
					'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ],
					'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ],
					'aliases' => [ 'de' => [ 'language' => 'de', 'value' => 'bar' ] ]
				],
				[ 'getLabelsChangeOpDeserializer', 'getDescriptionsChangeOpDeserializer', 'getAliasesChangeOpDeserializer' ],
			],
			[
				[
					'claims' => [ [ 'remove' => '', 'id' => 'test-guid' ] ],
				],
				[ 'getClaimsChangeOpDeserializer' ]
			],
			[
				[ 'sitelinks' => [ 'some-wiki' => [ 'site' => 'some-wiki' ] ] ],
				[ 'getSiteLinksChangeOpDeserializer' ]
			],
		];
	}

	private function getChangeOpDeserializerFactoryMock() {
		return $this->getMockBuilder( ChangeOpDeserializerFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getMockChangeOpDeserializer() {
		$deserializer = $this->getMock( ChangeOpDeserializer::class );
		$deserializer->method( 'createEntityChangeOp' )
			->willReturn( new ChangeOps() );

		return $deserializer;
	}

}
