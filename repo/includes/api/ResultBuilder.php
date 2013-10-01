<?php

namespace Wikibase\Api;

use ApiResult;
use InvalidArgumentException;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Entity;
use Wikibase\Item;
use Wikibase\Lib\Serializers\ByPropertyListSerializer;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Repo\WikibaseRepo;

class ResultBuilder {

	/**
	 * @var ApiResult
	 */
	protected $result;

	/**
	 * @var int
	 */
	protected $missingEntityCounter;

	//TODO check this output vs current output

	function __construct( $result ) {
		if( !$result instanceof ApiResult ){
			throw new InvalidArgumentException( 'Result builder must be constructed with an ApiWikibase' );
		}

		$this->result = $result;
		$this->missingEntityCounter = -1;
	}

	/**
	 * @param $wasSuccess bool|int|null
	 * @throws InvalidArgumentException
	 */
	public function markSuccess( $wasSuccess ) {
		$value = intval( $wasSuccess );
		if( $value !== 1 && $value !== 0 ){
			throw new InvalidArgumentException( '$wasSuccess must evaluate to either 1 or 0 when using intval()' );
		}
		$this->result->addValue( null, 'success', $value );
	}

	/**
	 * @param Entity[] $entities
	 * @throws InvalidArgumentException
	 */
	public function addEntities( $entities ){
		if( !is_array( $entities ) ){
			throw new InvalidArgumentException( '$entities must be an array' );
		}
		foreach( $entities as $entity ){
			$this->addEntity( $entity );
		}
	}

	/**
	 * @param Entity $entity
	 * @param array $props
	 * @throws InvalidArgumentException
	 */
	public function addEntity( Entity $entity, $props = array( 'labels', 'descriptions', 'aliases', 'sitelinks', 'claims' ) ) {
		if( !$entity instanceof Entity ){
			throw new InvalidArgumentException( '$entity must be instance of Entity' );
		}

		if( ! $entity->getId() instanceof EntityId ){
			$this->addMissingEntity();
		} else {
			$path = array( 'entities', $entity->getId()->getSerialization() );

			if( in_array( 'labels', $props ) ){
				$this->addLabels( $entity->getLabels(), $path );
			}
			if( in_array( 'descriptions', $props ) ){
				$this->addDescriptions( $entity->getDescriptions(), $path );
			}
			if( in_array( 'aliases', $props ) ){
				$this->addAliases( $entity->getAllAliases(), $path );
			}
			if( $entity instanceof Item && in_array( 'sitelinks', $props ) ){
				$this->addSitelinks( $entity->getSimpleSiteLinks(), $path );
			}
			if( in_array( 'claims', $props ) ){
				$this->addClaims( $entity->getClaims(), $path );
			}
		}
	}

	private function addMissingEntity() {
		$this->result->addValue( array( 'entities', $this->missingEntityCounter-- ), 'missing', 1 );
	}

	private function addLabels( $labels, $path = null ){
		$this->result->addValue( $path, 'labels', $labels );
	}

	private function addDescriptions( $descriptions, $path = null ){
		$this->result->addValue( $path, 'descriptions', $descriptions );
	}

	private function addAliases( $aliases, $path = null ){
		$this->result->addValue( $path, 'aliases', $aliases );
	}

	private function addSitelinks( $sitelinks, $path = null ){
		$this->result->addValue( $path, 'sitelinks', $sitelinks );
	}

	/**
	 * @param array|Claims $claims
	 * @param null $path
	 * @throws InvalidArgumentException
	 */
	public function addClaims( $claims, $path = null ) {
		if( !is_array( $claims ) && ! $claims instanceof Claims ){
			throw new InvalidArgumentException( '$claims must be an array' );
		}
		if( is_array( $claims ) ){
			$claims = new Claims( $claims );
		}
		$this->result->addValue( $path, 'claims', array() );
		$serializer = new ByPropertyListSerializer( 'claims', new ClaimSerializer() );
		$serialization = $serializer->getSerialized( $claims );
		$this->result->addValue( $path, 'claims', $serialization );
	}

}