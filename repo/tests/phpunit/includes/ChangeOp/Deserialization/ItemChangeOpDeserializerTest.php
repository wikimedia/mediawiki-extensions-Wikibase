<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ChangeOp\ChangeOpLabel;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\ChangeOpStatement;
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

	public function testGivenChangeRequestContainsLabels_returnsChangeOpLabels() {
		$deserializer = $this->newItemChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp(
			[ 'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ]
		);
		$this->assertTrue( $this->containsChangeOp( $changeOps, ChangeOpLabel::class ) );
	}

	private $changeOps = [
		ChangeOpLabel::class,
		ChangeOpDescription::class,
		ChangeOpAliases::class,
		ChangeOpStatement::class,
	];

	/**
	 * @dataProvider changeRequestProvider
	 */
	public function testGivenChangeRequestContainsFieldKey_returnsRespectiveChangeOp( array $changeRequest, $expectedChangeOps ) {
		$deserializer = $this->newItemChangeOpDeserializer();
		$changeOps = $deserializer->createEntityChangeOp( $changeRequest );

		foreach ( $expectedChangeOps as $dispatchedChangeOp ) {
			$this->assertTrue( $this->containsChangeOp( $changeOps, $dispatchedChangeOp ) );
		}
		foreach ( array_diff( $this->changeOps, $expectedChangeOps ) as $notDispatchedChangeOp ) {
			$this->assertFalse( $this->containsChangeOp( $changeOps, $notDispatchedChangeOp ) );
		}
	}

	public function changeRequestProvider() {
		return [
			[
				[ 'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ],
				[ ChangeOpLabel::class ],
			],
			[
				[ 'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ],
				[ ChangeOpDescription::class ],
			],
			[
				[ 'aliases' => [ 'de' => [ 'language' => 'de', 'value' => 'bar' ] ] ],
				[ ChangeOpAliases::class ],
			],
			[
				[
					'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ],
					'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ],
				],
				[ ChangeOpLabel::class, ChangeOpDescription::class ],
			],
			[
				[
					'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ],
					'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ],
					'aliases' => [ 'de' => [ 'language' => 'de', 'value' => 'bar' ] ]
				],
				[ ChangeOpLabel::class, ChangeOpDescription::class, ChangeOpAliases::class ],
			],
			// TODO: claims, sitelinks
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

}
