<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\Description;

/**
 * @covers Wikibase\DataModel\Term\Description
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DescriptionTest extends \PHPUnit_Framework_TestCase {

	public function testGetLanguageCodeReturnsSetValue() {
		$label = new Description( 'foo', 'bar' );
		$this->assertEquals( 'foo', $label->getLanguageCode() );
	}

	public function testGetTextReturnsSetValue() {
		$label = new Description( 'foo', 'bar' );
		$this->assertEquals( 'bar', $label->getText() );
	}

}
