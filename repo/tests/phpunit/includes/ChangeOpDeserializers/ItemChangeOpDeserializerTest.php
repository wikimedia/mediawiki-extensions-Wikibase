<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use RuntimeException;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ChangeOp\ChangeOpLabel;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\ChangeOpStatement;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOpDeserializers\ItemChangeOpDeserializer;
use Wikibase\Repo\ChangeOpDeserializers\WikibaseChangeOpDeserializerFactory;

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
		return new ItemChangeOpDeserializer(
			new WikibaseChangeOpDeserializerFactory( $this->getApiErrorReporter() )
		);
	}

	/**
	 * TODO: Refactor into mock class or test helper
	 *
	 * @param bool $expectsError
	 *
	 * @return ApiErrorReporter
	 */
	private function getApiErrorReporter( $expectsError = false ) {
		$errorReporter = $this->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();

		if ( !$expectsError ) {
			$errorReporter->expects( $this->never() )
				->method( 'dieError' );
		} else {
			$errorReporter->expects( $this->once() )
				->method( 'dieError' )
				->willReturnCallback( function( $description, $errorCode ) {
					throw new RuntimeException( $errorCode );
				} );
		}

		return $errorReporter;
	}
}
