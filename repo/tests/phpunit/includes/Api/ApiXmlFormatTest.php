<?php

namespace Wikibase\Repo\Tests\Api;

use ApiBase;
use DOMDocument;
use DOMXPath;
use HashSiteStore;
use MediaWiki\MediaWikiServices;
use TestSites;
use Wikibase\Lib\Tests\FakeCache;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\Api\GetClaims;
use Wikibase\Repo\Api\GetEntities;
use Wikibase\Repo\Api\SetAliases;
use Wikibase\Repo\Api\SetClaim;
use Wikibase\Repo\Api\SetDescription;
use Wikibase\Repo\Api\SetLabel;
use Wikibase\Repo\Api\SetQualifier;
use Wikibase\Repo\Api\SetReference;
use Wikibase\Repo\Api\SetSiteLink;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;
use Wikibase\Repo\SiteLinkTargetProvider;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Addshore
 */
class ApiXmlFormatTest extends ApiFormatTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setupSiteLinkGroups();
	}

	private function setupSiteLinkGroups() {
		global $wgWBRepoSettings;

		$customRepoSettings = $wgWBRepoSettings;
		$customRepoSettings['siteLinkGroups'] = [ 'wikipedia' ];
		$this->setMwGlobals( 'wgWBRepoSettings', $customRepoSettings );
		MediaWikiServices::getInstance()->resetServiceForTesting( 'SiteLookup' );
	}

	/**
	 * @covers \Wikibase\Repo\Api\GetEntities
	 */
	public function testGetEntitiesXmlFormat() {
		$entityRevision = $this->getNewEntityRevision( true );
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = [
			'action' => 'wbgetentities',
			'ids' => $entityId,
		];

		$module = $this->getApiModule( GetEntities::class, 'wbgetentities', $params );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );
		$actual = $this->replaceHashWithMock( $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'getentities' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\GetClaims
	 */
	public function testGetClaimsXmlFormat() {
		$entityRevision = $this->getNewEntityRevision( true );
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = [
			'action' => 'wbgetclaims',
			'entity' => $entityId,
		];

		$module = $this->getApiModule( GetClaims::class, 'wbgetclaims', $params );
		$actual = $this->executeApiModule( $module );
		$actual = $this->replaceHashWithMock( $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'getclaims' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\SetLabel
	 */
	public function testSetLabelXmlFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = [
			'action' => 'wbsetlabel',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => 'enGbLabel',
		];

		$module = $this->getApiModule( SetLabel::class, 'wbsetlabel', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setlabel' ), $actual );

		$params = [
			'action' => 'wbsetlabel',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => '',
		];

		$module = $this->getApiModule( SetLabel::class, 'wbsetlabel', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setlabel-removed' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\SetDescription
	 */
	public function testSetDescriptionXmlFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = [
			'action' => 'wbsetdescription',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => 'enGbDescription',
		];

		$module = $this->getApiModule( SetDescription::class, 'wbsetdescription', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setdescription' ), $actual );

		$params = [
			'action' => 'wbsetdescription',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => '',
		];

		$module = $this->getApiModule( SetDescription::class, 'wbsetdescription', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setdescription-removed' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\SetAliases
	 */
	public function testSetAliasesXmlFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = [
			'action' => 'wbsetaliases',
			'id' => $entityId,
			'language' => 'en-gb',
			'set' => 'AA|BB|CC',
		];

		$module = $this->getApiModule( SetAliases::class, 'wbsetaliases', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setaliases' ), $actual );

		$params = [
			'action' => 'wbsetaliases',
			'id' => $entityId,
			'language' => 'en-gb',
			'remove' => 'BB|CC',
		];

		$module = $this->getApiModule( SetAliases::class, 'wbsetaliases', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setaliases-removed' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\SetSiteLink
	 */
	public function testSetSitelinkXmlFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = [
			'action' => 'wbsetsitelink',
			'id' => $entityId,
			'linksite' => 'enwiki',
			'linktitle' => 'Japan',
			// TODO: Test badges in output.
		];
		$siteTargetProvider = new SiteLinkTargetProvider( new HashSiteStore( TestSites::getSites() ), [] );
		$this->setService( 'WikibaseRepo.SiteLinkTargetProvider', $siteTargetProvider );
		/** @var SetSiteLink $module */
		$module = $this->getApiModule( SetSiteLink::class, 'wbsetsitelink', $params, true );
		$siteLinkGlobalIdentifiersProvider = new SiteLinkGlobalIdentifiersProvider( $siteTargetProvider, new FakeCache() );
		$module->setServices( $siteLinkGlobalIdentifiersProvider );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );
		//If a URL has been added just remove it as it is not always present
		$actual = str_replace( 'url="https://en.wikipedia.org/wiki/Japan"', '', $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setsitelink' ), $actual );

		$params = [
			'action' => 'wbsetsitelink',
			'id' => $entityId,
			'linksite' => 'enwiki',
			//TODO test badges in output
		];

		/** @var SetSiteLink $module */
		$module = $this->getApiModule( SetSiteLink::class, 'wbsetsitelink', $params, true );
		$siteLinkGlobalIdentifiersProvider = new SiteLinkGlobalIdentifiersProvider( $siteTargetProvider, new FakeCache() );
		$module->setServices( $siteLinkGlobalIdentifiersProvider );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setsitelink-removed' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\SetClaim
	 */
	public function testSetClaimXmlFormat() {
		$this->getNewEntityRevision( true );

		$json = file_get_contents( __DIR__ . '/../../data/api/setclaim.json' );
		$json = $this->replaceIdsInString( $json );
		$params = [
			'action' => 'wbsetclaim',
			'claim' => $json,
		];

		$module = $this->getApiModule( SetClaim::class, 'wbsetclaim', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result );
		$actual = $this->replaceHashWithMock( $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setclaim' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\SetReference
	 */
	public function testSetReferenceXmlFormat() {
		$entityRevision = $this->getNewEntityRevision( true );
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$json = file_get_contents( __DIR__ . '/../../data/api/setreference.json' );
		$json = $this->replaceIdsInString( $json );
		$params = [
			'action' => 'wbsetreference',
			'statement' => $entityId . '$1111AAAA-43cb-ed6d-3adb-760e85bd17ee',
			'snaks' => $json,
		];

		$module = $this->getApiModule( SetReference::class, 'wbsetreference', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result );
		$actual = $this->replaceHashWithMock( $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setreference' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\SetQualifier
	 */
	public function testSetQualiferXmlFormat() {
		$entityRevision = $this->getNewEntityRevision( true );
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = [
			'action' => 'wbsetqualifier',
			'claim' => $entityId . '$1111AAAA-43cb-ed6d-3adb-760e85bd17ee',
			'property' => $this->lastPropertyId->getSerialization(),
			'value' => '"QualiValue"',
			'snaktype' => 'value',
		];

		$module = $this->getApiModule( SetQualifier::class, 'wbsetqualifier', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result );
		$actual = $this->replaceHashWithMock( $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setqualifier' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\EditEntity
	 */
	public function testEditEntityXmlFormat() {
		$this->storeNewProperty();
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$json = file_get_contents( __DIR__ . '/../../data/api/editentity.json' );
		$json = $this->replaceIdsInString( $json );

		$params = [
			'action' => 'wbeditentity',
			'id' => $entityId,
			'data' => $json,
		];

		$module = $this->getApiModule( EditEntity::class, 'wbeditentity', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );
		$actual = $this->replaceHashWithMock( $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'editentity' ), $actual );
	}

	private function getExpectedXml( $moduleIdentifier ) {
		$xml = file_get_contents( __DIR__ . '/../../data/api/' . $moduleIdentifier . '.xml' );
		$xml = $this->replaceIdsInString( $xml );
		$xml = $this->replaceHashWithMock( $xml );
		return $xml;
	}

	private function replaceIdsInString( $string ) {
		if ( $this->lastPropertyId !== null ) {
			$string = str_replace( '$propertyIdUnderTest', $this->lastPropertyId->getSerialization(), $string );
		}
		if ( $this->lastItemId !== null ) {
			$string = str_replace( '$itemIdUnderTest', $this->lastItemId->getSerialization(), $string );
		}
		return $string;
	}

	private function replaceHashWithMock( $string ) {
		$string = preg_replace( '/hash="\w+"/', 'hash="XXX"', $string );
		return $string;
	}

	/**
	 * @param string $xml
	 * @param string|null $entityId
	 *
	 * @return string
	 */
	private function removePageInfoAttributes( $xml, $entityId = null ) {
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$dom->loadXML( $xml );

		$xpath = new DOMXPath( $dom );
		if ( $entityId !== null ) {
			$element = $xpath->query( "//*[@id='$entityId']" )->item( 0 );
		} else {
			$element = $xpath->query( "//pageinfo" )->item( 0 );
		}

		$attributesToRemove = [ 'pageid', 'lastrevid', 'modified', 'title', 'ns' ];

		foreach ( $attributesToRemove as $attributeToRemove ) {
			$element->removeAttribute( $attributeToRemove );
		}

		return $dom->saveXML();
	}

	/**
	 * This mimics ApiMain::executeAction with the relevant parts,
	 * including setupExternalResponse where the printer is set.
	 * The module is then executed and results printed.
	 */
	private function executeApiModule( ApiBase $module ) {
		$printer = $module->getMain()->createPrinterByName( 'xml' );

		$module->execute();

		$printer->initPrinter();
		$printer->disable();

		$printer->execute();

		return $printer->getBuffer();
	}

}
