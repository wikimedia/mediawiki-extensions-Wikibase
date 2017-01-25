<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ItemChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\WikibaseChangeOpDeserializerFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\ChangeOpDeserializers\ItemChangeOpDeserializer
 *
 * @license GPL-2.0+
 */
class ItemChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenEmptyArray_returnsEmptyChangeOps() {
		$this->assertEmpty(
			$this->newItemChangeOpDeserializer()
				->createEntityChangeOp( [] )
				->getChangeOps()
		);
	}

	/**
	 * @dataProvider changeRequestProvider
	 */
	public function testGivenChangeRequestContainsFieldKey_returnsRespectiveChangeOp( array $changeRequest, $expectedMethods ) {
		$factory = $this->getMockBuilder( WikibaseChangeOpDeserializerFactory::class )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $expectedMethods as $method ) {
			$factory->expects( $this->once() )
				->method( $method )
				->willReturn( $this->getMockChangeOpDeserializer() );
		}

		/** @var $factory WikibaseChangeOpDeserializerFactory */
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

			// TODO: sitelinks
		];
	}

	private function containsChangeOp( ChangeOps $changeOps, $type ) {
		$containsChangeOp = false;

		foreach ( $changeOps->getChangeOps() as $changeOp ) {
			if ( $changeOp instanceof ChangeOps ) {
				$containsChangeOp = $containsChangeOp || $this->containsChangeOp( $changeOp, $type );
			}

			if ( $changeOp instanceof $type ) {
				return true;
			}
		}

		return $containsChangeOp;
	}

	private function newItemChangeOpDeserializer() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		return new ItemChangeOpDeserializer( new WikibaseChangeOpDeserializerFactory(
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			new TermChangeOpSerializationValidator( $wikibaseRepo->getTermsLanguages() ),
			$wikibaseRepo->getExternalFormatStatementDeserializer(),
			$wikibaseRepo->getStringNormalizer()
		) );
	}

	private function getMockChangeOpDeserializer() {
		$deserializer = $this->getMock( ChangeOpDeserializer::class );
		$deserializer->method( 'createEntityChangeOp' )
			->willReturn( new ChangeOps() );

		return $deserializer;
	}

}
