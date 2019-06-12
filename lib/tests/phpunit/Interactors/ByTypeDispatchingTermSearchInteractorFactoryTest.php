<?php

namespace Wikibase\Lib\Tests\Interactors;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Interactors\ByTypeDispatchingTermSearchInteractor;
use Wikibase\Lib\Interactors\ByTypeDispatchingTermSearchInteractorFactory;
use Wikibase\Lib\Interactors\TermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers \Wikibase\Lib\Interactors\ByTypeDispatchingTermSearchInteractorFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingTermSearchInteractorFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function provideInvalidConstructorArguments() {
		return [
			'non-string keys' => [
				[ 0 => $this->getMock( TermSearchInteractorFactory::class ) ]
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

		new ByTypeDispatchingTermSearchInteractorFactory( $factories );
	}

	public function testNewInteractorReturnsDispatchingTermSearchInteractorInstance() {
		$fooInteractorFactory = $this->getMock( TermSearchInteractorFactory::class );
		$fooInteractorFactory->expects( $this->any() )
			->method( 'newInteractor' )
			->will(
				$this->returnValue( $this->getMock( TermSearchInteractor::class ) )
			);

		$localInteractorFactory = $this->getMock( TermSearchInteractorFactory::class );
		$localInteractorFactory->expects( $this->any() )
			->method( 'newInteractor' )
			->will(
				$this->returnValue( $this->getMock( TermSearchInteractor::class ) )
			);

		$dispatchingFactory = new ByTypeDispatchingTermSearchInteractorFactory( [
			'item' => $fooInteractorFactory,
			'property' => $localInteractorFactory,
		] );

		$this->assertInstanceOf( ByTypeDispatchingTermSearchInteractor::class, $dispatchingFactory->newInteractor( 'en' ) );
	}

}
