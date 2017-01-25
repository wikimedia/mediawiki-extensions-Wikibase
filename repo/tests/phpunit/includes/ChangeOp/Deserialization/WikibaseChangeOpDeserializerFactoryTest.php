<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\LabelsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinksChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\WikibaseChangeOpDeserializerFactory;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\WikibaseChangeOpDeserializerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class WikibaseChangeOpDeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testGetLabelsChangeOpDeserializer() {
		$this->assertInstanceOf(
			LabelsChangeOpDeserializer::class,
			$this->newWikibaseChangeOpDeserializerFactory()->getLabelsChangeOpDeserializer()
		);
	}

	public function testGetDescriptionsChangeOpDeserializer() {
		$this->assertInstanceOf(
			DescriptionsChangeOpDeserializer::class,
			$this->newWikibaseChangeOpDeserializerFactory()->getDescriptionsChangeOpDeserializer()
		);
	}

	public function testGetAliasesChangeOpDeserializer() {
		$this->assertInstanceOf(
			AliasesChangeOpDeserializer::class,
			$this->newWikibaseChangeOpDeserializerFactory()->getAliasesChangeOpDeserializer()
		);
	}

	public function testGetClaimsChangeOpDeserializer() {
		$this->assertInstanceOf(
			ClaimsChangeOpDeserializer::class,
			$this->newWikibaseChangeOpDeserializerFactory()->getClaimsChangeOpDeserializer()
		);
	}

	public function testGetSiteLinksChangeOpDeserializer() {
		$this->assertInstanceOf(
			SiteLinksChangeOpDeserializer::class,
			$this->newWikibaseChangeOpDeserializerFactory()->getSiteLinksChangeOpDeserializer()
		);
	}

	private function newWikibaseChangeOpDeserializerFactory() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		return new WikibaseChangeOpDeserializerFactory(
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
			new TermChangeOpSerializationValidator( $wikibaseRepo->getTermsLanguages() ),
			new SiteLinkChangeOpSerializationValidator(
				new SiteLinkBadgeChangeOpSerializationValidator(
					$wikibaseRepo->getEntityTitleLookup(),
					[]
				)
			),
			$wikibaseRepo->getExternalFormatStatementDeserializer(),
			new SiteLinkTargetProvider( $wikibaseRepo->getSiteLookup(), [] ),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStringNormalizer(),
			[]
		);
	}

}
