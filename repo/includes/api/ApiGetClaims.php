<?php

namespace Wikibase;
use ApiBase, MWException;

/**
 * API module to get the claims for a Wikibase item.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiGetClaims extends Api {
	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$entityFactory = EntityFactory::singleton();

		if ( !$entityFactory->isPrefixedId( $params['id'] ) ) {
			$params['id'] = ItemObject::getIdPrefix() . $params['id'];
		}

		if ( EntityFactory::singleton()->getEntityTypeFromPrefixedId( $params['id'] ) !== \Wikibase\Item::ENTITY_TYPE ) {
			$this->dieUsage( 'Entity type not found', 'entity-type-not-found' );
		}

		foreach ( $params['properties'] as $i => $id ) {
			if ( !$entityFactory->isPrefixedId( $id ) ) {
				$params['properties'][$i] = PropertyObject::getIdPrefix() . $id;
				$this->getResult()->setWarning( 'Assuming plain numeric ID refers to a property. '
						. 'Please use qualified IDs instead.' );
			}
		}

		$entityContent = EntityContentFactory::singleton()->getFromPrefixedId( $params['id'] );

		if ( $entityContent === null ) {
			$this->dieUsage( 'Entity not found, snak not created', 'entity-not-found' );
		}

		$entity = $entityContent->getEntity();

		$filteredClaims = array();
		if ( $entity instanceof StatementAggregate ) {
			foreach ( $entity->getStatements() as $statement) {
				if ( isset( $params['rank'] ) && $params['rank'] > $statement->getRank() ) continue;
				if ( isset( $params['key'] ) && !in_array( $statement->getGuid(), $params['key'] ) ) continue;
				if ( isset( $params['properties'] ) && !in_array( PropertyObject::getIdPrefix() . $statement->getPropertyId(), $params['properties'] ) ) continue;
				$filteredClaims[] = $statement;
			}
		}
		elseif ( $entity instanceof ClaimAggregate ) {
			foreach ( $entity->getClaims() as $claim) {
				if ( isset( $params['key'] ) && !in_array( $claim->getGuid(), $params['key'] ) ) continue;
				if ( isset( $params['properties'] ) && !in_array( PropertyObject::getIdPrefix() . $claim->getPropertyId(), $params['properties'] ) ) continue;
				$filteredClaims[] = $statement;
			}
		}
		else {
			throw new MWException( 'Entity does not support Claim objects' );
		}

		$options = new EntitySerializationOptions();
		$options->setLanguages( $params['languages'] );
		$options->setProps( $params['props'] );
		$options->setUseKeys( $this->getUsekeys() );

		$claimSerializer = new ClaimSerializer( $this->getResult(), $options );
		$res = $this->getResult();

		$idx = 0;
		foreach ( $filteredClaims as $claim ) {
			$claimSerialization = $claimSerializer->getSerialized( $claim );
			$claimPath = array( 'claims', $this->getUsekeys() ? $claim->getGuid(): $idx );
			$idx++;

			foreach ( $claimSerialization as $key => $value ) {
				$res->addValue( $claimPath, $key, $value );
			}
		}

	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'properties' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_REQUIRED => false,
			),
			'keys' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_REQUIRED => false,
			),
			'rank' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
			),
			'props' => array(
				ApiBase::PARAM_TYPE => array( 'references', 'qualifiers' ),
				ApiBase::PARAM_DFLT => 'qualifiers|references',
				ApiBase::PARAM_ISMULTI => true,
			),
			'languages' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
				ApiBase::PARAM_ISMULTI => true,
			),
		) );
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array(
			'id' => array( 'The identifier for the entity, including the prefix.',
				"Use an id for an item."
			),
			'properties' => array( 'To be listed the Claims must contain the given property identifier.',
				"Use zero, one or many identifiers."
			),
			'key' => array( 'To be listed the Claims must contain the given hash key.',
				"Use zero, one or many hash keys."
			),
			'rank' => array( 'To be listed the Claims must have a rank above the given value.',
				"Use none or a single value."
			),
			'props' => array( 'For each Claim the following props are listed.',
				"Use one or many props."
			),
			'languages' => array( 'By default the internationalized values are returned in all available languages.',
				'Filter down output to one or more provided languages.'
			),
		);
	}

	/**
	 * @see ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to get the claims for a Wikibase items.'
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	/**
	 * @see ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbgetclaims&id=q51'
			=> 'Get item number q42 with language attributes in all available languages',
			'api.php?action=wbgetclaims&id=q51&languages=en'
			=> 'Get item number q42 with language attributes in English language',
			'api.php?action=wbgetclaims&id=q51&property=p11'
			=> 'Get item number q42 with all claims that have property p11 filtered out',
		);
	}

	/**
	 * @see ApiBase::getHelpUrls()
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbgetclaims';
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}