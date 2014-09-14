<?php

namespace Wikibase\Test;

use Wikibase\Repo\ParserOutput\ConfigVarsParserOutputGenerator;

/**
 * @covers Wikibase\Repo\ParserOutput\HtmlParserOutputGeneratorFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class HtmlParserOutputGeneratorFactory extends \PHPUnit_Framework_TestCase {

	public function testCreateHtmlParserOutputGenerator() {
		$factory = $this->getHtmlParserOutputGeneratorFactory();
	}

	private function getHtmlParserOutputGeneratorFactory() {
		return new ConfigVarsParserOutputGenerator(
			$this->getMock( 'Wikibase\Lib\Store\EntityInfoBuilderFactory' ),
			$this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' ),
			$this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' )
		);
	}

}
