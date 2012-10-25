<?php

namespace Wikibase;
use ApiBase, MWException;

/**
 * API module to get the data for one or more Wikibase items.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiGetEntities extends Api {

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		if ( !( isset( $params['ids'] ) XOR ( isset( $params['sites'] ) && isset( $params['titles'] ) ) ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-id-xor-wikititle' )->text(), 'id-xor-wikititle' );
		}

		$missing = 0;

		if ( !isset( $params['ids'] ) ) {
			$params['ids'] = array();
			$numSites = count( $params['sites'] );
			$numTitles = count( $params['titles'] );
			$max = max( $numSites, $numTitles );

			if ( $numSites === 0 || $numTitles === 0 ) {
				$this->dieUsage( $this->msg( 'wikibase-api-id-xor-wikititle' )->text(), 'id-xor-wikititle' );
			}
			else {
				$idxSites = 0;
				$idxTitles = 0;

				for ( $k = 0; $k < $max; $k++ ) {
					$siteId = $params['sites'][$idxSites++];
					$title = Utils::squashToNFC( $params['titles'][$idxTitles++] );

					$id = ItemHandler::singleton()->getIdForSiteLink( $siteId, $title );

					if ( $id ) {
						$params['ids'][] = ItemObject::getIdPrefix() . intval( $id );
					}
					else {
						$this->getResult()->addValue( 'entities', (string)(--$missing),
							array( 'site' => $siteId, 'title' => $title, 'missing' => "" )
						);
					}

					if ( $idxSites === $numSites ) {
						$idxSites = 0;
					}

					if ( $idxTitles === $numTitles ) {
						$idxTitles = 0;
					}
				}
			}
		}

		// B/C: assume non-prefixed IDs refer to items
		$entityFactory = EntityFactory::singleton();
		foreach ( $params['ids'] as $i => $id ) {
			if ( !$entityFactory->isPrefixedId( $id ) ) {
				$params['ids'][$i] = ItemObject::getIdPrefix() . $id;
				$this->getResult()->setWarning( 'Assuming plain numeric ID refers to an item. '
						. 'Please use qualified IDs instead.' );
			}
		}

		$params['ids'] = array_unique( $params['ids'] );

		if ( in_array( 'sitelinks/urls', $params['props'] ) ) {
			$props = array_flip( array_values( $params['props'] ) );
			//unset( $props['sitelinks/urls'] );
			$props['sitelinks'] = true;
			$props = array_keys( $props );
		}
		else {
			$props = $params['props'];
		}
		$options = new EntitySerializationOptions();
		$options->setLanguages( $params['languages'] );
		$options->setSortDirection( $params['dir'] );
		$options->setProps( $props );
		$options->setUseKeys( $this->getUsekeys() );

		$entitySerializer = new EntitySerializer( $this->getResult(), $options );
		$itemSerializer = new ItemSerializer( $this->getResult(), $options );

		// loop over all items
		foreach ( $params['ids'] as $entityId ) {
			switch ( EntityFactory::singleton()->getEntityTypeFromPrefixedId( $entityId ) ) {
			case 'item':
				$this->handleEntity( $entityId, $params, $props, $itemSerializer );
				break;
			default:
				$this->handleEntity( $entityId, $params, $props, $entitySerializer );
			}
		}

		if ( !$this->getUsekeys() ) {
			$this->getResult()->setIndexedTagName_internal( array( 'entities' ), 'entity' );
		}
		$success = true;

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);
	}

	/**
	 * Fetches the entity with provided id and adds its serialization to the output.
	 *
	 * @since 0.2
	 *
	 * @param string $id
	 * @param array $params
	 * @param array $props
	 * @param EntitySerializer $entitySerializer
	 *
	 * @throws MWException
	 */
	protected function handleEntity( $id, array $params, array $props, EntitySerializer $entitySerializer ) {
		$entityFactory = EntityFactory::singleton();
		$entityContentFactory = EntityContentFactory::singleton();

		$res = $this->getResult();

		// key should be numeric to get the correct behaviour
		// note that this setting depends upon "setIndexedTagName_internal"
		$numId = $entityFactory->getUnprefixedId( $id );
		$entityPath = array( 'entities', $this->getUsekeys() ? $id: $numId );

		// later we do a getContent but only if props are defined
		if ( $params['props'] !== array() ) {
			$page = $entityContentFactory->getWikiPageForPrefixedId( $id );

			if ( $page->exists() ) {
				// as long as getWikiPageForId only returns ids for legal items this holds
				/**
				 * @var $entityContent EntityContent
				 */
				$entityContent = $page->getContent();

				// this should not happen unless a page is not what we assume it to be
				// that is, we want this to be a little more solid if something ges wrong
				if ( is_null( $entityContent ) ) {
					$res->addValue( $entityPath, 'id', $id );
					$res->addValue( $entityPath, 'illegal', "" );
					return;
				}

				// default stuff to add that comes from the title/page/revision
				if ( in_array( 'info', $props ) ) {
					$res->addValue( $entityPath, 'pageid', intval( $page->getId() ) );
					$title = $page->getTitle();
					$res->addValue( $entityPath, 'ns', intval( $title->getNamespace() ) );
					$res->addValue( $entityPath, 'title', $title->getPrefixedText() );
					$revision = $page->getRevision();

					if ( $revision !== null ) {
						$res->addValue( $entityPath, 'lastrevid', intval( $revision->getId() ) );
						$res->addValue( $entityPath, 'touched', $revision->getTimestamp( TS_ISO_8601 ) );
						$res->addValue( $entityPath, 'length', intval( $revision->getSize() ) );
					}

					$res->addValue( $entityPath, 'count', intval( $page->getCount() ) );
				}

				$entity = $entityContent->getEntity();
				$entitySerialization = $entitySerializer->getSerialized( $entity );

				foreach ( $entitySerialization as $key => $value ) {
					$res->addValue( $entityPath, $key, $value );
				}
			}
			else {
				$res->addValue( $entityPath, 'missing', "" );
			}
		} else {
			$type = $entityFactory->getEntityTypeFromPrefixedId( $id );
			$res->addValue( $entityPath, 'id', $id );
			$res->addValue( $entityPath, 'type', $type );
		}
	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'ids' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'sites' => array(
				ApiBase::PARAM_TYPE => $this->getSiteLinkTargetSites()->getGlobalIdentifiers(),
				ApiBase::PARAM_ISMULTI => true,
			),
			'titles' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'props' => array(
				ApiBase::PARAM_TYPE => array( 'info', 'sitelinks', 'aliases', 'labels', 'descriptions', 'sitelinks/urls', 'statements' ),
				ApiBase::PARAM_DFLT => 'info|sitelinks|aliases|labels|descriptions|statements',
				ApiBase::PARAM_ISMULTI => true,
			),
			'sort' => array(
				// This could be done like the urls, where sitelinks/title sort on the title field
				// and sitelinks/site sort on the site code.
				ApiBase::PARAM_TYPE => array( 'sitelinks' ),
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
			),
			'dir' => array(
				ApiBase::PARAM_TYPE => array(
					EntitySerializationOptions::SORT_ASC,
					EntitySerializationOptions::SORT_DESC,
					EntitySerializationOptions::SORT_NONE
				),
				ApiBase::PARAM_DFLT => EntitySerializationOptions::SORT_ASC,
				ApiBase::PARAM_ISMULTI => false,
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
		return array_merge( parent::getParamDescription(), array(
			'ids' => 'The IDs of the items to get the data from',
			'sites' => array( 'Identifier for the site on which the corresponding page resides',
				"Use together with 'title', but only give one site for several titles or several sites for one title."
			),
			'titles' => array( 'The title of the corresponding page',
				"Use together with 'sites', but only give one site for several titles or several sites for one title."
			),
			'props' => array( 'The names of the properties to get back from each item.',
				"Will be further filtered by any languages given."
			),
			'sort' => array( 'The names of the properties to sort.',
				"Use together with 'dir' to give the sort order.",
				"Note that this will change due to name clash (ie. sort should work on all entities)."
			),
			'dir' => array( 'The sort order for the given properties.',
				"Use together with 'sort' to give the properties to sort.",
				"Note that this will change due to name clash (ie. dir should work on all entities)."
			),
			'languages' => array( 'By default the internationalized values are returned in all available languages.',
				'This parameter allows filtering these down to one or more languages by providing one or more language codes.'
			),
		) );
	}

	/**
	 * @see ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to get the data for multiple Wikibase items.'
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'wrong-class', 'info' => $this->msg( 'wikibase-api-wrong-class' )->text() ),
			array( 'code' => 'id-xor-wikititle', 'info' => $this->msg( 'wikibase-api-id-xor-wikititle' )->text() ),
			array( 'code' => 'no-such-item', 'info' => $this->msg( 'wikibase-api-no-such-item' )->text() ),
			array( 'code' => 'not-recognized', 'info' => $this->msg( 'wikibase-api-not-recognized' )->text() ),
		) );
	}

	/**
	 * @see ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbgetentities&ids=42'
			=> 'Get item number 42 with language attributes in all available languages',
			'api.php?action=wbgetentities&ids=42&languages=en'
			=> 'Get item number 42 with language attributes in English language',
			'api.php?action=wbgetentities&sites=en&titles=Berlin&languages=en'
			=> 'Get the item for page "Berlin" on the site "en", with language attributes in English language',
		);
	}

	/**
	 * @see ApiBase::getHelpUrls()
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbgetentities';
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
