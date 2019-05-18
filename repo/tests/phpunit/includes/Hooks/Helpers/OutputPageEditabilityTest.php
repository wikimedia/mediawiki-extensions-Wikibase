<?php

namespace Wikibase\Repo\Tests\Hooks\Helpers;

use DerivativeContext;
use OutputPage;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use RequestContext;
use Title;
use WebRequest;
use Wikibase\Repo\Hooks\Helpers\OutputPageEditability;

/**
 * @covers \Wikibase\Repo\Hooks\Helpers\OutputPageEditability
 *
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 */
class OutputPageEditabilityTest extends TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider nonEditableOutputPageProvider
	 */
	public function testGivenPageIsNotEditable_returnsFalse( OutputPage $outputPage ) {
		$editability = new OutputPageEditability();
		$this->assertFalse( $editability->validate( $outputPage ) );
	}

	public function nonEditableOutputPageProvider() {
		$out = $this->newOutputPage();
		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'quickUserCan' )
			->willReturn( false );
		$out->setTitle( $title );
		yield 'user does not have edit permission' => [ $out ];

		$request = $this->getMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( true );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$context->setTitle( new Title() );
		yield 'diff page' => [ new OutputPage( $context ) ];

		$out = $this->newOutputPage();
		$out->setRevisionId( 123 );
		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 321 );
		$out->setTitle( $title );
		yield 'not latest revision' => [ $out ];

		$out = $this->newOutputPage();
		$out->setPrintable();
		yield 'print view' => [ $out ];
	}

	/**
	 * @dataProvider editableOutputPageProvider
	 */
	public function testGivenPageIsEditable_returnsTrue( OutputPage $outputPage ) {
		$editability = new OutputPageEditability();
		$this->assertTrue( $editability->validate( $outputPage ) );
	}

	public function editableOutputPageProvider() {
		yield [ $this->getDefaultEditableOutputPage() ];
		yield [ $this->getNullRevisionEditableOutputPage() ];
		yield [ $this->getNonExistingTitleEditableOutputPage() ];
	}

	private function getDefaultEditableOutputPage() {
		$outputPage = $this->newOutputPage();

		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'quickUserCan' )
			->with( 'edit' )
			->willReturn( true );

		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$request = $this->getMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( false );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$context->setTitle( $title );

		$outputPage->setRevisionId( 123 );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 123 );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	private function getNullRevisionEditableOutputPage() {
		$outputPage = $this->newOutputPage();

		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'quickUserCan' )
			->with( 'edit' )
			->willReturn( true );

		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$request = $this->getMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( false );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$context->setTitle( $title );

		$outputPage->setRevisionId( null );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	private function getNonExistingTitleEditableOutputPage() {
		$outputPage = $this->newOutputPage();

		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( false );
		$title->expects( $this->exactly( 2 ) )
			->method( 'quickUserCan' )
			->withConsecutive( [ 'edit' ], [ 'create' ] )
			->willReturn( true );

		$request = $this->getMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( false );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$context->setTitle( $title );

		$outputPage->setRevisionId( 123 );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 123 );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	/**
	 * @return OutputPage
	 */
	private function newOutputPage() {
		$outputPage = new OutputPage( new DerivativeContext( RequestContext::getMain() ) );
		$outputPage->setTitle( new Title() );

		return $outputPage;
	}

}
