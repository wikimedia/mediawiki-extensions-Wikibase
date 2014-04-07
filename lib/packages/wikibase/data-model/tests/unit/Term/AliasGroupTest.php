<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\AliasGroup;

/**
 * @covers Wikibase\DataModel\Term\AliasGroup
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AliasGroupTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorSetsValues() {
		$language = 'en';
		$aliases = array( 'foo', 'bar', 'baz' );

		$group = new AliasGroup( $language, $aliases );

		$this->assertEquals( $language, $group->getLanguageCode() );
		$this->assertEquals( $aliases, $group->getAliases() );
	}

}
