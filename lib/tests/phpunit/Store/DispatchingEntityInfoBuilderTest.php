<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Edrsf\DispatchingEntityInfoBuilder;
use Wikibase\Edrsf\EntityInfoBuilder;

/**
 * @class Wikibase\Edrsf\DispatchingEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 */
class DispatchingEntityInfoBuilderTest extends \PHPUnit_Framework_TestCase {

	public function provideInvalidConstructorArguments() {
		return [
			'empty builder list' => [ [] ],
			'invalid repository name as a key' => [ [ 'fo:oo' => $this->getMock( EntityInfoBuilder::class ) ] ],
			'not an EntityInfoBuilder provided as a builder' => [ [ '' => new ItemId( 'Q111' ) ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidArguments_constructorThrowsException( array $args ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new \Wikibase\Edrsf\DispatchingEntityInfoBuilder( $args );
	}

	public function testGetEntityInfoMergesEntityInfoFromAllBuilders() {
		$localBuilder = $this->getMock( EntityInfoBuilder::class );
		$localBuilder->expects( $this->any() )
			->method( 'getEntityInfo' )
			->will( $this->returnValue( new \Wikibase\Edrsf\EntityInfo( [
				'Q11' => [ 'id' => 'Q11', 'type' => 'item' ],
			] ) ) );

		$otherBuilder = $this->getMock( EntityInfoBuilder::class );
		$otherBuilder->expects( $this->any() )
			->method( 'getEntityInfo' )
			->will( $this->returnValue( new \Wikibase\Edrsf\EntityInfo( [
				'other:Q22' => [ 'id' => 'other:Q22', 'type' => 'item' ],
			] ) ) );

		$dispatchingBuilder = new DispatchingEntityInfoBuilder( [
			'' => $localBuilder, 'other' => $otherBuilder
		] );

		$this->assertEquals(
			new \Wikibase\Edrsf\EntityInfo( [
				'Q11' => [ 'id' => 'Q11', 'type' => 'item' ],
				'other:Q22' => [ 'id' => 'other:Q22', 'type' => 'item' ],
			] ),
			$dispatchingBuilder->getEntityInfo()
		);
	}

	public function testResolveRidirectsCallsTheMethodOnAllBuilders() {
		$localBuilder = $this->getMock( EntityInfoBuilder::class );
		$localBuilder->expects( $this->atLeastOnce() )
			->method( 'resolveRedirects' );

		$otherBuilder = $this->getMock( EntityInfoBuilder::class );
		$otherBuilder->expects( $this->atLeastOnce() )
			->method( 'resolveRedirects' );

		$dispatchingBuilder = new DispatchingEntityInfoBuilder( [
			'' => $localBuilder, 'other' => $otherBuilder
		] );

		$dispatchingBuilder->resolveRedirects();
	}

	public function testCollectTermsCallsTheMethodOnAllBuilders() {
		$languages = [ 'de', 'en' ];

		$localBuilder = $this->getMock( EntityInfoBuilder::class );
		$localBuilder->expects( $this->atLeastOnce() )
			->method( 'collectTerms' )
			->with( null, $languages );

		$otherBuilder = $this->getMock( EntityInfoBuilder::class );
		$otherBuilder->expects( $this->atLeastOnce() )
			->method( 'collectTerms' )
			->with( null, $languages );

		$dispatchingBuilder = new DispatchingEntityInfoBuilder( [
			'' => $localBuilder, 'other' => $otherBuilder
		] );

		$dispatchingBuilder->collectTerms( null, $languages );
	}

	public function testCollectDataTypesCallsTheMethodOnAllBuilders() {
		$localBuilder = $this->getMock( EntityInfoBuilder::class );
		$localBuilder->expects( $this->atLeastOnce() )
			->method( 'collectDataTypes' );

		$otherBuilder = $this->getMock( \Wikibase\Edrsf\EntityInfoBuilder::class );
		$otherBuilder->expects( $this->atLeastOnce() )
			->method( 'collectDataTypes' );

		$dispatchingBuilder = new \Wikibase\Edrsf\DispatchingEntityInfoBuilder( [
			'' => $localBuilder, 'other' => $otherBuilder
		] );

		$dispatchingBuilder->collectDataTypes();
	}

	public function provideRemoveMissingArguments() {
		return [
			[ 'keep-redirects' ],
			[ 'remove-redirects' ],
		];
	}

	/**
	 * @dataProvider provideRemoveMissingArguments
	 */
	public function testRemoveMissingCallsTheMethodOnAllBuilders( $redirectsFlag ) {
		$localBuilder = $this->getMock( EntityInfoBuilder::class );
		$localBuilder->expects( $this->atLeastOnce() )
			->method( 'removeMissing' )
			->with( $redirectsFlag );

		$otherBuilder = $this->getMock( EntityInfoBuilder::class );
		$otherBuilder->expects( $this->atLeastOnce() )
			->method( 'removeMissing' )
			->with( $redirectsFlag );

		$dispatchingBuilder = new DispatchingEntityInfoBuilder( [
			'' => $localBuilder, 'other' => $otherBuilder
		] );

		$dispatchingBuilder->removeMissing( $redirectsFlag );
	}

	public function testRemoveEntityInfoCallsTheMethodOnRelevantRepositorysBuilderWithItsOwnEntitiesOnly() {
		$localIdOne = new ItemId( 'Q11' );
		$localIdTwo = new ItemId( 'Q12' );
		$otherId = new ItemId( 'other:Q22' );
		$anotherId = new ItemId( 'another:Q33' );

		$localBuilder = $this->getMock( EntityInfoBuilder::class );
		$localBuilder->expects( $this->atLeastOnce() )
			->method( 'removeEntityInfo' )
			->with( [ $localIdOne, $localIdTwo ] );

		$otherBuilder = $this->getMock( EntityInfoBuilder::class );
		$otherBuilder->expects( $this->atLeastOnce() )
			->method( 'removeEntityInfo' )
			->with( [ $otherId ] );

		$dispatchingBuilder = new DispatchingEntityInfoBuilder( [
			'' => $localBuilder, 'other' => $otherBuilder,
		] );

		$dispatchingBuilder->removeEntityInfo( [ $localIdOne, $otherId, $localIdTwo, $anotherId ] );
	}

	public function testRetainEntityInfoCallsTheMethodOnRelevantRepositorysBuilderWithItsOwnEntitiesOnly() {
		$localIdOne = new ItemId( 'Q11' );
		$localIdTwo = new ItemId( 'Q12' );
		$otherId = new ItemId( 'other:Q22' );
		$anotherId = new ItemId( 'another:Q33' );

		$localBuilder = $this->getMock( EntityInfoBuilder::class );
		$localBuilder->expects( $this->atLeastOnce() )
			->method( 'retainEntityInfo' )
			->with( [ $localIdOne, $localIdTwo ] );

		$otherBuilder = $this->getMock( EntityInfoBuilder::class );
		$otherBuilder->expects( $this->atLeastOnce() )
			->method( 'retainEntityInfo' )
			->with( [ $otherId ] );

		$dispatchingBuilder = new DispatchingEntityInfoBuilder( [
			'' => $localBuilder, 'other' => $otherBuilder,
		] );

		$dispatchingBuilder->retainEntityInfo( [ $localIdOne, $otherId, $localIdTwo, $anotherId ] );
	}

}
