<?php

namespace Wikibase\Test;

use PHPUnit_Framework_Assert as Assert;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\WikibaseRepo;

/**
 * EntityModificationTestHelper
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityModificationTestHelper {

	private $idParser;

	private $serializer;

	private $deserializer;

	private $repository;

	public function __construct() {
		$this->idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$this->serializer = WikibaseRepo::getDefaultInstance()->getInternalEntitySerializer();
		$this->deserializer = WikibaseRepo::getDefaultInstance()->getInternalEntityDeserializer();
		$this->repository = new MockRepository();
	}

	/**
	 * @return MockRepository
	 */
	public function getRepository() {
		return $this->repository;
	}

	/**
	 * Adds a list of entities to the test data.
	 *
	 * @param array $entities A list of Entity object or array structures representing entities.
	 *        If the entity does not have an ID and the corresponding array key is a string,
	 *        they key is used as the entity ID.
	 */
	public function putEntities( $entities ) {
		foreach ( $entities as $key => $entity ) {
			$id = is_string( $key ) ? $key : null;
			$this->putEntity( $entity, $id );
		}
	}

	/**
	 * Adds a list of redirects to the test data.
	 *
	 * @param array $redirects A list of EntityRedirect objects, EntityId objects or strings.
	 *        If a value in the list is not an EntityRedirect, a redirect is constructed from
	 *        the corresponding array key and value; the key is parsed as an EntityId, the value
	 *        is also parsed if it's a string.
	 */
	public function putRedirects( $redirects ) {
		foreach ( $redirects as $key => $redirect ) {

			if ( !( $redirect instanceof EntityRedirect ) ) {
				$from = $this->idParser->parse( $key );

				if ( $redirect instanceof EntityId ) {
					$target = $redirect;
				} else {
					$target = $this->idParser->parse( $redirect );
				}

				$redirect = new EntityRedirect( $from, $target );
			}

			$this->repository->putRedirect( $redirect );
		}
	}

	/**
	 * @param array|Entity $entity
	 * @param EntityId|string|null $id Overrides any id in $entity
	 */
	public function putEntity( $entity, $id = null ) {
		if ( is_array( $entity ) ) {
			$entity = $this->unserializeEntity( $entity, $id );
		}

		if ( $id !== null ) {
			if ( is_string( $id ) ) {
				$id = $this->idParser->parse( $id );
			}

			$entity->setId( $id );
		}

		$this->repository->putEntity( $entity );
	}

	public function getEntity( $id ) {
		if ( is_string( $id ) ) {
			$id = $this->idParser->parse( $id );
		}

		return $this->repository->getEntity( $id );
	}

	/**
	 * @param array $data
	 * @param EntityId|string|null $id
	 *
	 * @return object
	 */
	public function unserializeEntity( $data, $id = null ) {
		if ( $id !== null ) {
			if ( is_string( $id ) ) {
				$id = $this->idParser->parse( $id );
			}

			$data['id'] = $id->getSerialization();
			$data['type'] = $id->getEntityType();
		}

		$entity = $this->deserializer->deserialize( $data );
		return $entity;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return array
	 */
	public function serializeEntity( Entity $entity ) {
		$data = $this->serializer->serialize( $entity );

		return $data;
	}

	/**
	 * Strip any fields we will likely not have in the arrays that are provided as
	 * expected values. This includes empty fields, and automatic id or hash fields.
	 *
	 * @param $data
	 */
	private function unsetSpuriousFieldsRecursively( &$data ) {
		// unset empty fields
		foreach ( $data as $key => &$value ) {
			if ( $key === 'hash' || $key === 'id' ) {
				unset( $data[$key] );
			} elseif ( $value === array() ) {
				unset( $data[$key] );
			} elseif ( is_array( $value ) ) {
				$this->unsetSpuriousFieldsRecursively( $value );
			}
		}
	}

	/**
	 * Compares two entity structures and asserts that they are equal.
	 * Top level keys not present in the $expected structure are ignored.
	 * Some fields ('id' and 'hash') in lower level structures are ignored.
	 *
	 * @param array|Entity $expected
	 * @param array|Entity $actual
	 * @param string $message
	 */
	public function assertEntityEquals( $expected, $actual, $message = '' ) {
		if ( $expected instanceof Entity ) {
			$expected = $this->serializeEntity( $expected );
		}

		if ( $actual instanceof Entity ) {
			$actual = $this->serializeEntity( $actual );
		}

		foreach ( array_keys( $actual ) as $key ) {
			if ( !array_key_exists( $key, $expected ) ) {
				unset( $actual[$key] );
			}
		}

		foreach ( $expected as $key => $value ) {
			Assert::assertArrayHasKey( $key, $actual, $message );

			if ( is_array( $actual[$key] ) ) {
				$this->unsetSpuriousFieldsRecursively( $actual[$key] );
			}

			Assert::assertEquals( $value, $actual[$key], "$message [$key]" );
		}
	}

	/**
	 * Asserts that the revision with the given ID has a summary matching $regex
	 *
	 * @param string $regex|array The regex to match, or an array to build a regex from
	 * @param int $revid
	 */
	public function assertRevisionSummary( $regex, $revid ) {
		if ( is_array( $regex ) ) {
			$r = '';

			foreach ( $regex as $s ) {
				if ( strlen( $r ) > 0 ) {
					$r .= '.*';
				}

				$r .= preg_quote( $s, '!' );
			}

			$regex = "!$r!";
		}

		$entry = $this->repository->getLogEntry( $revid );
		Assert::assertNotNull( $entry, "revision not found: $revid" );
		Assert::assertRegExp( $regex, $entry['summary'] );
	}

}
