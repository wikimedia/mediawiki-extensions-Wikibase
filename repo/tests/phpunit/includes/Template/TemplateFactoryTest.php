<?php

namespace Wikibase\Tests\Template;

use Wikibase\Template\TemplateFactory;

/**
 * @covers Wikibase\Template\TemplateFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 * @author Thiemo Mättig
 */
class TemplateFactoryTest extends \MediaWikiTestCase {

	public function testGetDefaultInstance() {
		$factory = TemplateFactory::getDefaultInstance();

		$this->assertInstanceOf( 'Wikibase\Template\TemplateFactory', $factory );
		$this->assertNotEmpty( $factory->getTemplates() );
	}

	public function testConstructor() {
		$factory = new TemplateFactory( array( 'tmpl1' => '<a>$1</a>' ) );

		$this->assertSame( array( 'tmpl1' => '<a>$1</a>' ), $factory->getTemplates() );
		$this->assertSame( '<a>$1</a>', $factory->getTemplate( 'tmpl1' ) );
		$this->assertNull( $factory->getTemplate( 'missing' ) );
	}

	public function testAddTemplates() {
		$factory = new TemplateFactory();
		$factory->addTemplates( array( 'tmpl1' => '<a>$1</a>' ) );

		$this->assertSame( array( 'tmpl1' => '<a>$1</a>' ), $factory->getTemplates() );
		$this->assertSame( '<a>$1</a>', $factory->getTemplate( 'tmpl1' ) );
		$this->assertNull( $factory->getTemplate( 'missing' ) );
	}

	public function testAddTemplate() {
		$factory = new TemplateFactory();
		$factory->addTemplate( 'tmpl1', '<a>$1</a>' );

		$this->assertSame( array( 'tmpl1' => '<a>$1</a>' ), $factory->getTemplates() );
		$this->assertSame( '<a>$1</a>', $factory->getTemplate( 'tmpl1' ) );
		$this->assertNull( $factory->getTemplate( 'missing' ) );
	}

	public function testRender() {
		$factory = new TemplateFactory();
		$factory->addTemplate( 'tmpl1', '<a>$1</a>' );

		$this->assertSame( '<a>A</a>', $factory->render( 'tmpl1', 'A' ) );
		$this->assertSame( '<missing>', $factory->render( 'missing' ) );
	}

	public function testGivenIndentedMultilineString_tabsAreStripped() {
		$factory = new TemplateFactory( array( 'tmpl1' => "<div>\n\t<a>$1</a>\n</div>" ) );

		$this->assertSame( "<div>\n<a>A</a>\n</div>", $factory->render( 'tmpl1', 'A' ) );
	}

}
