<?php

namespace Wikibase\Test\Repo\Api;

use ApiBase;
use ApiMain;
use FauxRequest;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\EntityRevision;
use Wikibase\Repo\Api\SetSiteLink;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\MockSiteStore;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adam Shorland
 */
class ApiXmlFormatTest extends \MediaWikiTestCase {

	/**
	 * @var PropertyId|null
	 */
	private $lastPropertyId;

	/**
	 * @var PropertyId|null
	 */
	private $lastItemId;

	public function testGetEntitiesXmlFormat() {
		$entityRevision = $this->getNewEntityRevision( true );
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbgetentities',
			'ids' => $entityId
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\GetEntities', 'wbgetentities', $params );
		$result = $this->doApiRequest( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'getentities' ), $actual );
	}

	public function testGetClaimsXmlFormat() {
		$entityRevision = $this->getNewEntityRevision( true );
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbgetclaims',
			'entity' => $entityId
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\GetClaims', 'wbgetclaims', $params );
		$actual = $this->doApiRequest( $module );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'getclaims' ), $actual );
	}

	public function testSetLabelXmlFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbsetlabel',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => 'enGbLabel',
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetLabel', 'wbsetlabel', $params, true );
		$result = $this->doApiRequest( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setlabel' ), $actual );
	}

	public function testSetDescriptionXmlFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbsetdescription',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => 'enGbDescription',
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetDescription', 'wbsetdescription', $params, true );
		$result = $this->doApiRequest( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setdescription' ), $actual );
	}

	public function testSetAliasesXmlFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbsetaliases',
			'id' => $entityId,
			'language' => 'en-gb',
			'set' => 'AA|BB|CC',
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetAliases', 'wbsetaliases', $params, true );
		$result = $this->doApiRequest( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setaliases' ), $actual );
	}

	public function testSetSitelinkXmlFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbsetsitelink',
			'id' => $entityId,
			'linksite' => 'enwiki',
			'linktitle' => 'Japan',
			//TODO test basges in output
		);

		/** @var SetSiteLink $module */
		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetSiteLink', 'wbsetsitelink', $params, true );
		$siteTaregtProvider = new SiteLinkTargetProvider( MockSiteStore::newFromTestSites(), array() );
		$module->setServices( $siteTaregtProvider );
		$result = $this->doApiRequest( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );
		//If a URL has been added just remove it as it is not always present
		$actual = str_replace( 'url="https://en.wikipedia.org/wiki/Japan"', '', $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setsitelink' ), $actual );
	}

	public function testSetClaimXmlFormat() {
		$this->getNewEntityRevision( true );

		$json = file_get_contents( __DIR__ . '/../../data/api/setclaim.json' );
		$json = $this->replaceIdsInString( $json );
		$params = array(
			'action' => 'wbsetclaim',
			'claim' => $json,
		);

		/** @var SetSiteLink $module */
		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetClaim', 'wbsetclaim', $params, true );
		$result = $this->doApiRequest( $module );
		$actual = $this->removePageInfoAttributes( $result );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setclaim' ), $actual );
	}

	public function testSetReferenceXmlFormat() {
		$entityRevision = $this->getNewEntityRevision( true );
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$json = file_get_contents( __DIR__ . '/../../data/api/setreference.json' );
		$json = $this->replaceIdsInString( $json );
		$params = array(
			'action' => 'wbsetreference',
			'statement' => $entityId . '$1111AAAA-43cb-ed6d-3adb-760e85bd17ee',
			'snaks' => $json,
		);

		/** @var SetSiteLink $module */
		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetReference', 'wbsetreference', $params, true );
		$result = $this->doApiRequest( $module );
		$actual = $this->removePageInfoAttributes( $result );
		$actual = $this->replaceHashWithMock( $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setreference' ), $actual );
	}

	public function testSetQualiferXmlFormat() {
		$entityRevision = $this->getNewEntityRevision( true );
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbsetqualifier',
			'claim' => $entityId . '$1111AAAA-43cb-ed6d-3adb-760e85bd17ee',
			'property' => $this->lastPropertyId->getSerialization(),
			'value' => '"QualiValue"',
			'snaktype' => 'value',
		);

		/** @var SetSiteLink $module */
		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetQualifier', 'wbsetqualifier', $params, true );
		$result = $this->doApiRequest( $module );
		$actual = $this->removePageInfoAttributes( $result );
		$actual = $this->replaceHashWithMock( $actual );

		$this->assertXmlStringEqualsXmlString( $this->getExpectedXml( 'setqualifier' ), $actual );
	}

	public function testEditEntityXmlFormat() {
		$this->storeNewProperty();
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$json = file_get_contents( __DIR__ . '/../../data/api/editentity.json' );
		$json = $this->replaceIdsInString( $json );

		$params = array(
			'action' => 'wbeditentity',
			'id' => $entityId,
			'data' => $json,
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\EditEntity', 'wbeditEntity', $params, true );
		$result = $this->doApiRequest( $module );
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
	 * @param string $entityId
	 *
	 * @return string
	 */
	private function removePageInfoAttributes( $xml, $entityId = null ) {
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->loadXML( $xml );

		$xpath = new \DOMXPath( $dom );
		if ( $entityId !== null ) {
			$element = $xpath->query( "//*[@id='$entityId']" )->item( 0 );
		} else {
			$element = $xpath->query( "//pageinfo" )->item( 0 );
		}

		$attributesToRemove = array( 'pageid', 'lastrevid', 'modified', 'title', 'ns' );

		foreach ( $attributesToRemove as $attributeToRemove ) {
			$element->removeAttribute( $attributeToRemove );
		}

		return $dom->saveXML();
	}

	/**
	 * @param string $moduleClass
	 * @param string $moduleName
	 * @param array $params
	 * @param bool $needsToken
	 *
	 * @return ApiMain
	 */
	private function getApiModule( $moduleClass, $moduleName, array $params, $needsToken = false ) {
		global $wgUser;

		if ( $needsToken ) {
			$params['token'] = $wgUser->getEditToken();
		}
		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );

		/**
		 * This has to be set before the Wikibase Api module is instansiated due to the ApiHelperFactory
		 * using $api->getResult()->getIsRawMode().
		 */
		$main->getResult()->setRawMode( true );

		return new $moduleClass( $main, $moduleName );
	}

	private function doApiRequest( ApiBase $module ) {
		$printer = $module->getMain()->createPrinterByName( 'xml' );

		$module->execute();

		$printer->initPrinter();
		$printer->disable();

		$printer->execute();

		return $printer->getBuffer();
	}

	private function getNewEntityRevision( $withData = false ) {
		$entityRevision = $this->storeNewItem();

		if ( $withData ) {
			$this->storeNewProperty();
			$entityRevision = $this->storePresetDataInStatement( $entityRevision, $this->lastPropertyId );
		}

		return $entityRevision;
	}

	private function storeNewProperty() {
		global $wgUser;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$property = Property::newFromType( 'string' );
		$entityRevision = $store->saveEntity( $property, 'testing', $wgUser, EDIT_NEW );
		$this->lastPropertyId = $entityRevision->getEntity()->getId();
	}

	private function storeNewItem() {
		global $wgUser;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = new Item();
		$entityRevision = $store->saveEntity( $item, 'testing', $wgUser, EDIT_NEW );
		$this->lastItemId = $entityRevision->getEntity()->getId();

		return $entityRevision;
	}

	private function storePresetDataInStatement( EntityRevision $entityRevision, PropertyId $propertyId ) {
		global $wgUser;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		/** @var Item $item */
		$item = $entityRevision->getEntity();
		$snak = new PropertyNoValueSnak( $propertyId );
		$guid = $item->getId()->getSerialization() . '$1111AAAA-43cb-ed6d-3adb-760e85bd17ee';
		$item->getStatements()->addNewStatement( $snak, null, null, $guid );
		$entityRevision = $store->saveEntity( $item, 'testing more!', $wgUser );

		return $entityRevision;
	}

}
