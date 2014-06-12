<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Diff\PropertyDiffer;
use Wikibase\DataModel\Entity\Property;

/**
 * @covers Wikibase\DataModel\Entity\Diff\PropertyDiffer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyDifferTest extends \PHPUnit_Framework_TestCase {

	public function testGivenPropertyWithOnlyType_constructionDiffIsEmpty() {
		$differ = new PropertyDiffer();
		$this->assertTrue( $differ->getConstructionDiff( Property::newFromType( 'string' ) )->isEmpty() );
	}

	public function testGivenPropertyWithOnlyType_destructionDiffIsEmpty() {
		$differ = new PropertyDiffer();
		$this->assertTrue( $differ->getDestructionDiff( Property::newFromType( 'string' ) )->isEmpty() );
	}

}

