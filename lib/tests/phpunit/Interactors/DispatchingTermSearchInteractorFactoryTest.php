<?php

namespace Wikibase\Lib\Tests\Interactors;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Edrsf\DispatchingTermSearchInteractorFactory;
use Wikibase\Edrsf\TermSearchInteractor;
use Wikibase\Edrsf\TermSearchInteractorFactory;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers Wikibase\Lib\Interactors\DispatchingTermSearchInteractorFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 */
class DispatchingTermSearchInteractorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function provideInvalidConstructorArguments() {
		return [
			'non-string keys' => [
				[ 0 => $this->getMock( \Wikibase\Edrsf\TermSearchInteractorFactory::class ) ]
			],
			'not a TermSearchInteractorFactory as a value' => [
				[ 'item' => new ItemId( 'Q123' ) ]
			],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidArguments_constructorThrowsException( $factories ) {
		$this->setExpectedException( ParameterAssertionException::class );

		new DispatchingTermSearchInteractorFactory( $factories );
	}

	public function testNewInteractorReturnsDispatchingTermSearchInteractorInstance() {
		$fooInteractorFactory = $this->getMock( TermSearchInteractorFactory::class );
		$fooInteractorFactory->expects( $this->any() )
			->method( 'newInteractor' )
			->will(
				$this->returnValue( $this->getMock( \Wikibase\Edrsf\TermSearchInteractor::class ) )
			);

		$localInteractorFactory = $this->getMock( TermSearchInteractorFactory::class );
		$localInteractorFactory->expects( $this->any() )
			->method( 'newInteractor' )
			->will(
				$this->returnValue( $this->getMock( TermSearchInteractor::class ) )
			);

		$dispatchingFactory = new DispatchingTermSearchInteractorFactory( [
			'item' => $fooInteractorFactory,
			'property' => $localInteractorFactory,
		] );

		$this->assertInstanceOf( \Wikibase\Edrsf\DispatchingTermSearchInteractor::class, $dispatchingFactory->newInteractor( 'en' ) );
	}

}
