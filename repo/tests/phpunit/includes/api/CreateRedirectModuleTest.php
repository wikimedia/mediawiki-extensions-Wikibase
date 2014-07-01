<?php

namespace Wikibase\Test\Api;

use ApiMain;
use FauxRequest;
use Language;
use UsageException;
use Wikibase\Api\ApiErrorReporter;
use Wikibase\Api\CreateRedirectModule;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Repo\Interactors\CreateRedirectInteractor;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Api\CreateRedirectModule
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CreateRedirectModuleTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var MockRepository
	 */
	private $repo = null;

	public function setUp() {
		parent::setUp();

		$this->repo = new MockRepository();

		// empty item
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q11' ) );
		$this->repo->putEntity( $item );

		// non-empty item
		$item->setLabel( 'en', 'Foo' );
		$item->setId( new ItemId( 'Q12' ) );
		$this->repo->putEntity( $item );

		// a property
		$prop = Property::newEmpty();
		$prop->setId( new PropertyId( 'P11' ) );
		$this->repo->putEntity( $prop );

		// another property
		$prop->setId( new PropertyId( 'P12' ) );
		$this->repo->putEntity( $prop );

		// redirect
		$redirect = new EntityRedirect( new ItemId( 'Q22' ), new ItemId( 'Q12' ) );
		$this->repo->putRedirect( $redirect );
	}

	/**
	 * @param array $params
	 *
	 * @return CreateRedirectModule
	 */
	private function newApiModule( $params ) {
		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );
		$module = new CreateRedirectModule( $main, 'wbcreateredirect' );

		$idParser = new BasicEntityIdParser();

		$errorReporter = new ApiErrorReporter(
			$module,
			WikibaseRepo::getDefaultInstance()->getExceptionLocalizer(),
			Language::factory( 'en' )
		);

		$summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();

		$module->setServices(
			$idParser,
			$errorReporter,
			new CreateRedirectInteractor(
				$this->repo,
				$this->repo,
				$summaryFormatter
			)
		);

		return $module;
	}

	private function callApiModule( $params ) {
		global $wgUser;

		if ( !isset( $params['token'] ) ) {
			$params['token'] = $wgUser->getToken();
		}

		$module = $this->newApiModule( $params );

		$module->execute();
		$result = $module->getResult();

		return $result->getData();
	}

	private function assertSuccess( $result ) {
		$this->assertArrayHasKey( 'success', $result );
		$this->assertEquals( 1, $result['success'] );
	}

	public function setRedirectProvider_success() {
		return array(
			'redirect empty entity' => array( 'Q11', 'Q12' ),
			'update redirect' => array( 'Q22', 'Q11' ),
		);
	}

	/**
	 * @dataProvider setRedirectProvider_success
	 */
	public function testSetRedirect_success( $from, $to ) {
		$params = array( 'from' => $from, 'to' => $to );
		$result = $this->callApiModule( $params );

		$this->assertSuccess( $result );

		$fromId = new ItemId( $from );
		$toId = new ItemId( $to );

		try {
			$this->repo->getEntity( $fromId );
			$this->fail( 'getEntity( ' . $from . ' ) did not throw an UnresolvedRedirectException' );
		} catch ( UnresolvedRedirectException $ex ) {
			$this->assertEquals( $toId->getSerialization(), $ex->getRedirectTargetId()->getSerialization() );
		}
	}

	public function setRedirectProvider_failure() {
		return array(
			'bad source id' => array( 'xyz', 'Q12', 'invalid-entity-id' ),
			'bad target id' => array( 'Q11', 'xyz', 'invalid-entity-id' ),

			'source not found' => array( 'Q77', 'Q12', 'no-such-entity' ),
			'target not found' => array( 'Q11', 'Q77', 'no-such-entity' ),
			'target is a redirect' => array( 'Q11', 'Q22', 'target-is-redirect' ),
			'target is incompatible' => array( 'Q11', 'P11', 'target-is-incompatible' ),

			'source not empty' => array( 'Q12', 'Q11', 'not-empty' ),
			'can\'t redirect' => array( 'P11', 'P12', 'cant-redirect' ),
		);
	}

	/**
	 * @dataProvider setRedirectProvider_failure
	 */
	public function testSetRedirect_failure( $from, $to, $expectedCode ) {
		$params = array( 'from' => $from, 'to' => $to );

		try {
			$this->callApiModule( $params );
			$this->fail( 'API did not fail with error ' . $expectedCode . ' as expected!' );
		} catch ( UsageException $ex ) {
			$this->assertEquals( $expectedCode, $ex->getCodeString() );
		}
	}

}
