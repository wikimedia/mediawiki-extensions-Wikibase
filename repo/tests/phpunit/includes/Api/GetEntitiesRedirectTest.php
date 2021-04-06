<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\GetEntities
 *
 * Test for redirect resolution in the wbgetentities API module
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 * @group medium
 */
class GetEntitiesRedirectTest extends ApiTestCase {

	private function createEntity( $label ) {
		$entity = new Item();
		$entity->setLabel( 'en', $label );

		$store = WikibaseRepo::getEntityStore();
		$rev = $store->saveEntity( $entity, 'GetEntitiesRedirectTest', $this->getTestUser()->getUser(), EDIT_NEW );
		$id = $rev->getEntity()->getId();

		return $id;
	}

	private function createEntityRedirect( EntityId $target ) {
		$id = $this->createEntity( 'Dummy' );

		$redirect = new EntityRedirect( $id, $target );

		$store = WikibaseRepo::getEntityStore();
		$store->saveRedirect( $redirect, 'GetEntitiesRedirectTest', $this->getTestUser()->getUser(), EDIT_UPDATE );

		return $id;
	}

	public function testResolveRedirect() {
		$user = $this->getTestUser()->getUser();

		// NOTE: We test all cases in a single test function run, so we only have to
		//       set up the entities in the database once.
		$targetId = $this->createEntity( 'GetEntitiesRedirectTest' );
		$redirectId = $this->createEntityRedirect( $targetId );
		$doubleRedirectId = $this->createEntityRedirect( $redirectId );

		$targetKey = $targetId->getSerialization();
		$redirectKey = $redirectId->getSerialization();
		$doubleRedirectKey = $doubleRedirectId->getSerialization();

		// if redirect resolution is enabled, the redirect should be resolved
		$params = [];
		$params['action'] = 'wbgetentities';
		$params['token'] = $user->getToken();
		$params['ids'] = $redirectId->getSerialization();
		list( $result,, ) = $this->doApiRequest( $params, null, false, $user );

		$this->assertArrayHasKey( 'entities', $result );
		$this->assertArrayHasKey( $redirectKey, $result['entities'],
			'the id from the request should be used as a key in the result' );
		$this->assertArrayNotHasKey( $targetKey, $result['entities'],
			'the id from the request should be used as a key in the result' );
		$this->assertArrayHasKey( 'labels', $result['entities'][$redirectKey],
			'the redirect should be resolve to a full entity' );
		$this->assertEquals(
			$targetKey,
			$result['entities'][$redirectKey]['id'],
			'the entity id should be the id of the redirect target'
		);

		// double redirects should be treated like a missing entity
		$params['ids'] = $doubleRedirectId->getSerialization();
		list( $result,, ) = $this->doApiRequest( $params, null, false, $user );

		$this->assertArrayHasKey( 'entities', $result );
		$this->assertArrayHasKey( $doubleRedirectKey, $result['entities'],
			'the id from the request should be used as a key in the result' );
		$this->assertArrayNotHasKey( $redirectKey, $result['entities'],
			'the id from the request should be used as a key in the result' );
		$this->assertEquals(
			$doubleRedirectKey,
			$result['entities'][$doubleRedirectKey]['id'],
			'the reported entitiy id should be the id of the redirect'
		);
		$this->assertArrayHasKey( 'missing', $result['entities'][$doubleRedirectKey],
			'the entity should be labeled as missing' );
		$this->assertArrayNotHasKey( 'labels', $result['entities'][$doubleRedirectKey],
			'the unresolved redirect should not have labels' );

		// if redirect resolution is disabled, the redirect should be treated like a missing entity
		$params['redirects'] = 'no';
		$params['ids'] = $redirectId->getSerialization();
		list( $result,, ) = $this->doApiRequest( $params, null, false, $user );

		$this->assertArrayHasKey( 'entities', $result );
		$this->assertArrayHasKey( $redirectKey, $result['entities'],
			'the id from the request should be used as a key in the result' );
		$this->assertArrayNotHasKey( $targetKey, $result['entities'],
			'the id from the request should be used as a key in the result' );
		$this->assertEquals(
			$redirectKey,
			$result['entities'][$redirectKey]['id'],
			'the reported entitiy id should be the id of the redirect'
		);
		$this->assertArrayHasKey( 'missing', $result['entities'][$redirectKey],
			'the entity should be labeled as missing' );
		$this->assertArrayNotHasKey( 'labels', $result['entities'][$redirectKey],
			'the unresolved redirect should not have labels' );
	}

}
