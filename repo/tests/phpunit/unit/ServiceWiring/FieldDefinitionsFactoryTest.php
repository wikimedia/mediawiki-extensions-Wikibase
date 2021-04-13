<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\Search\Fields\FieldDefinitionsFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FieldDefinitionsFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] ) );
		$this->mockService( 'WikibaseRepo.TermsLanguages',
			new StaticContentLanguages( [] ) );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray() );

		$this->assertInstanceOf(
			FieldDefinitionsFactory::class,
			$this->getService( 'WikibaseRepo.FieldDefinitionsFactory' )
		);
	}

}
