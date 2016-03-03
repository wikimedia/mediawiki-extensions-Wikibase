<?php

namespace Wikibase\Repo\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityView;

/**
 * @covers WikibaseRepo.entitytypes.php
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityTypesTest extends PHPUnit_Framework_TestCase {

	private function getRegistry() {
		return require __DIR__  . '/../../../WikibaseRepo.entitytypes.php';
	}

	public function provideEntityTypes() {
		return array(
			array( 'item' ),
			array( 'property' )
		);
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testViewFactoryCallback( $entityType ) {
		$registry = $this->getRegistry()[$entityType];

		$entityView = call_user_func(
			$registry['view-factory-callback'],
			'en',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( array() ),
			$this->getMock( EditSectionGenerator::class )
		);

		$this->assertInstanceOf( EntityView::class, $entityView );
	}

}
