<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\Label;

/**
 * @covers Wikibase\DataModel\Term\Label
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LabelTest extends \PHPUnit_Framework_TestCase {

	public function testGetLanguageCodeReturnsSetValue() {
		$label = new Label( 'foo', 'bar' );
		$this->assertEquals( 'foo', $label->getLanguageCode() );
	}

	public function testGetTextReturnsSetValue() {
		$label = new Label( 'foo', 'bar' );
		$this->assertEquals( 'bar', $label->getText() );
	}

}
