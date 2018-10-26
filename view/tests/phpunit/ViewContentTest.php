<?php

namespace Wikibase\View\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\View\ViewContent;

/**
 * @covers \Wikibase\View\ViewContent
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 */
class ViewContentTest extends TestCase {

	public function testAccessors() {
		$html = '<lorem>';
		$placeholders = [ 'a' => 'b' ];

		$viewContent = new ViewContent( $html, $placeholders );

		$this->assertSame( $html, $viewContent->getHtml() );
		$this->assertSame( $placeholders, $viewContent->getPlaceholders() );
	}

}
