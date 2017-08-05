<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\DispatchingTermBuffer;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers Wikibase\Lib\Store\DispatchingTermBuffer
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 */
class DispatchingTermBufferTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidTermBufferProvider
	 */
	public function testGivenInvalidTermBuffers_exceptionIsThrown( array $termBuffers ) {
		$this->setExpectedException( ParameterAssertionException::class );
		new DispatchingTermBuffer( $termBuffers );
	}

	public function invalidTermBufferProvider() {
		return [
			'no buffers given' => [ [] ],
			'not an implementation of TermBuffers given as a lookup' => [
				[ '' => new ItemId( 'Q123' ) ],
			],
			'non-string keys' => [
				[
					'' => $this->getMock( TermBuffer::class ),
					100 => $this->getMock( TermBuffer::class ),
				],
			],
			'repo name containing colon' => [
				[
					'' => $this->getMock( TermBuffer::class ),
					'fo:oo' => $this->getMock( TermBuffer::class ),
				],
			],
		];
	}

	/**
	 * @dataProvider entityIdsFromDifferentReposProvider
	 */
	public function testPrefetchTermsGroupsEntityIdsByRepo( array $ids, array $callsPerRepo ) {
		$fooTermBuffer = $this->getMock( TermBuffer::class );
		$fooTermBuffer->expects( $this->exactly( $callsPerRepo['foo'] ) )
			->method( 'prefetchTerms' )
			->with( $this->callback( function ( array $ids ) {
				/** @var EntityId[] $ids */
				foreach ( $ids as $id ) {
					if ( $id->getRepositoryName() !== 'foo' ) {
						return false;
					}
				}
				return true;
			} ) );

		$localTermBuffer = $this->getMock( TermBuffer::class );
		$localTermBuffer->expects( $this->exactly( $callsPerRepo[''] ) )
			->method( 'prefetchTerms' )
			->with( $this->callback( function ( array $ids ) {
				/** @var EntityId[] $ids */
				foreach ( $ids as $id ) {
					if ( $id->getRepositoryName() !== '' ) {
						return false;
					}
				}
				return true;
			} ) );

		$dispatcher = new DispatchingTermBuffer( [ 'foo' => $fooTermBuffer, '' => $localTermBuffer ] );
		$dispatcher->prefetchTerms( $ids );
	}

	public function entityIdsFromDifferentReposProvider() {
		return [
			'empty array' => [ [], [ 'foo' => 0, '' => 0, ], ],
			'0 EntityIds for foo, 1 for local' => [
				[ new ItemId( 'Q123' ) ],
				[ 'foo' => 0, '' => 1, ],
			],
			'1 EntityIds for foo, 0 for local' => [
				[ new ItemId( 'foo:Q123' ) ],
				[ 'foo' => 1, '' => 0, ],
			],
			'2 EntityIds for foo, 1 for local' => [
				[ new ItemId( 'foo:Q123' ), new ItemId( 'Q123' ), new ItemId( 'foo:Q42' ) ],
				[ 'foo' => 1, '' => 1, ],
			],
		];
	}

	public function testGetPrefetchedTerm() {
		$dispatcher = new DispatchingTermBuffer( [
			'' => $this->getTermBufferWithTerms( [
				'label' => [ 'en' => 'Vienna', 'de' => 'Wien' ],
				'description' => [ 'en' => 'Vienna description', 'de' => 'Wien description' ],
			] ),
			'foo' => $this->getTermBufferWithTerms( [
				'label' => [ 'en' => 'Berlin', 'de' => 'Berlin' ],
				'description' => [ 'en' => 'Berlin description', 'de' => 'Berlin Beschreibung' ],
			] ),
		] );

		$this->assertSame(
			'Vienna',
			$dispatcher->getPrefetchedTerm( new ItemId( 'Q123' ), 'label', 'en' )
		);
		$this->assertSame(
			'Wien description',
			$dispatcher->getPrefetchedTerm( new ItemId( 'Q123' ), 'description', 'de' )
		);
		$this->assertSame(
			'Berlin',
			$dispatcher->getPrefetchedTerm( new ItemId( 'foo:Q123' ), 'label', 'en' )
		);
		$this->assertSame(
			'Berlin Beschreibung',
			$dispatcher->getPrefetchedTerm( new ItemId( 'foo:Q123' ), 'description', 'de' )
		);
	}

	public function testGivenUnknownRepository_getPrefetchedTermReturnsNull() {
		$dispatcher = new DispatchingTermBuffer( [
			'foo' => $this->getMock( TermBuffer::class ),
		] );

		$this->assertNull( $dispatcher->getPrefetchedTerm( new ItemId( 'bar:Q123' ), 'label', 'en' ) );
	}

	public function testGetLabel() {
		$dispatcher = new DispatchingTermBuffer( [
			'' => $this->getTermBufferWithTerms( [ 'label' => [ 'en' => 'Vienna', 'de' => 'Wien' ] ] ),
			'foo' => $this->getTermBufferWithTerms( [ 'label' => [ 'en' => 'Rome', 'de' => 'Rom' ] ] ),
		] );

		$this->assertSame( 'Vienna', $dispatcher->getLabel( new ItemId( 'Q123' ), 'en' ) );
		$this->assertSame( 'Wien', $dispatcher->getLabel( new ItemId( 'Q123' ), 'de' ) );
		$this->assertSame( 'Rom', $dispatcher->getLabel( new ItemId( 'foo:Q123' ), 'de' ) );
	}

	public function testGetLabels() {
		$dispatcher = new DispatchingTermBuffer( [
			'' => $this->getTermBufferWithTerms(
				[ 'label' => [ 'en' => 'Vienna', 'de' => 'Wien', 'fr' => 'Vienne' ] ]
			),
			'foo' => $this->getTermBufferWithTerms( [ 'label' => [ 'en' => 'Rome', 'de' => 'Rom' ] ] ),
		] );

		$this->assertSame(
			[ 'en' => 'Vienna', 'de' => 'Wien' ],
			$dispatcher->getLabels( new ItemId( 'Q123' ), [ 'en', 'de' ] )
		);
		$this->assertSame(
			[ 'en' => 'Rome' ],
			$dispatcher->getLabels( new ItemId( 'foo:Q123' ), [ 'en' ] )
		);
	}

	/**
	 * @dataProvider getLabelsParamsProvider
	 */
	public function testGetTermsOfType_prefetchesTerms( $entityId, $languageCodes ) {
		$termBuffer = $this->getMock( TermBuffer::class );
		$termBuffer->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with( [ $entityId ], [ 'label' ], $languageCodes );

		$dispatcher = new DispatchingTermBuffer( [
			'foo' => $termBuffer,
		] );
		$dispatcher->getLabels( $entityId, $languageCodes );
	}

	public function getLabelsParamsProvider() {
		return [
			[ new ItemId( 'foo:Q123' ), [ 'de' ] ],
			[ new ItemId( 'foo:Q42' ), [ 'en', 'fr', 'de' ] ],
		];
	}

	public function testGivenUnknownTerm_termDoesNotAppearInResults() {
		$dispatcher = new DispatchingTermBuffer( [
			'' => $this->getTermBufferWithTerms(
				[ 'label' => [ 'en' => 'Vienna', 'fr' => 'Vienne', 'de' => false ] ]
			),
		] );

		$labels = $dispatcher->getLabels( new ItemId( 'Q123' ), [ 'de', 'en' ] );
		$this->assertArrayHasKey( 'en', $labels );
		$this->assertArrayNotHasKey( 'de', $labels );
	}

	private function getTermBufferWithTerms( $terms ) {
		$termBuffer = $this->getMock( TermBuffer::class );
		$termBuffer->method( 'getPrefetchedTerm' )
			->will( $this->returnCallback( function ( EntityId $entityId, $termType, $languageCode ) use ( $terms ) {
				return $terms[$termType][$languageCode];
			} ) );

		return $termBuffer;
	}

}
