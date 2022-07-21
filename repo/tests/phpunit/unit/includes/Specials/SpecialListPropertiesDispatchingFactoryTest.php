<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\FederatedProperties\SpecialListFederatedProperties;
use Wikibase\Repo\Specials\SpecialListProperties;
use Wikibase\Repo\Specials\SpecialListPropertiesDispatchingFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialListPropertiesDispatchingFactory
 *
 * @group SpecialPage
 * @group Wikibase
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0-or-later
 */
class SpecialListPropertiesDispatchingFactoryTest extends TestCase {
	public function testFactoryNoFederation() {
		$specialPage = SpecialListPropertiesDispatchingFactory::factory(
			new DataTypeFactory( [] ),
			WikibaseRepo::getEntityIdHtmlLinkFormatterFactory(),
			$this->createMock( EntityTitleLookup::class ),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory(),
			new SettingsArray( [
				'federatedPropertiesEnabled' => true,
				'federatedPropertiesSourceScriptUrl' => 'https://wiki.example/w/',
			] ),
			WikibaseRepo::getStore()
		);

		$this->assertInstanceOf( SpecialListFederatedProperties::class, $specialPage );
	}

	public function testFactoryFederation() {
		$specialPage = SpecialListPropertiesDispatchingFactory::factory(
			new DataTypeFactory( [] ),
			WikibaseRepo::getEntityIdHtmlLinkFormatterFactory(),
			$this->createMock( EntityTitleLookup::class ),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory(),
			new SettingsArray( [ 'federatedPropertiesEnabled' => false ] ),
			WikibaseRepo::getStore()
		);

		$this->assertInstanceOf( SpecialListProperties::class, $specialPage );
	}
}
