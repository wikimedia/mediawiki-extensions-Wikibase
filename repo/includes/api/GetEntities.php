<?php

namespace Wikibase\Api;

use ApiBase;
use MWException;

use Wikibase\Lib\Serializers\EntitySerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Utils;
use Wikibase\StoreFactory;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\EntityContentFactory;

/**
 * API module to get the data for one or more Wikibase entities.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class GetEntities extends ApiWikibase {

	/**
	 * @var \Wikibase\StringNormalizer
	 */
	protected $stringNormalizer;

	public function __construct( \ApiMain $main, $name, $prefix = '' ) {
		parent::__construct( $main, $name, $prefix );

		$this->stringNormalizer = WikibaseRepo::getDefaultInstance()->getStringNormalizer();
	}

	/**
	 * @see \ApiBase::execute()
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();

		if ( !( isset( $params['ids'] ) XOR ( !empty( $params['sites'] ) && !empty( $params['titles'] ) ) ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Either provide the item "ids" or pairs of "sites" and "titles" for corresponding pages', 'param-missing' );
		}

		if ( !isset( $params['ids'] ) ) {
			$siteLinkCache = StoreFactory::getStore()->newSiteLinkCache();
			$siteStore = \SiteSQLStore::newInstance();
			$itemByTitleHelper = new ItemByTitleHelper( $this, $siteLinkCache, $siteStore, $this->stringNormalizer );
			$params['ids'] = $itemByTitleHelper->getEntityIds( $params['sites'], $params['titles'], $params['normalize'] );
		}

		$params['ids'] = array_unique( $params['ids'] );

		if ( in_array( 'sitelinks/urls', $params['props'] ) ) {
			$props = array_flip( array_values( $params['props'] ) );
			$props['sitelinks'] = true;
			$props = array_keys( $props );
		} else {
			$props = $params['props'];
		}

		foreach ( $params['ids'] as $entityId ) {
			$this->handleEntity( $entityId, $params, $props );
		}

		if ( $this->getResult()->getIsRawMode() ) {
			$this->getResult()->setIndexedTagName_internal( array( 'entities' ), 'entity' );
		}

		$success = true;

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Fetches the entity with provided id and adds its serialization to the output.
	 *
	 * @since 0.2
	 *
	 * @param string $id
	 * @param array $params
	 * @param array $props
	 *
	 * @throws MWException
	 */
	protected function handleEntity( $id, array $params, array $props ) {
		wfProfileIn( __METHOD__ );

		$entityContentFactory = EntityContentFactory::singleton();

		$res = $this->getResult();

		$entityId = EntityId::newFromPrefixedId( $id );

		if ( !$entityId ) {
			//TODO: report as missing instead?
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "Invalid id: $id", 'no-such-entity' );
		}

		// key should be numeric to get the correct behavior
		// note that this setting depends upon "setIndexedTagName_internal"
		//FIXME: if we get different kinds of entities at once, $entityId->getNumericId() may not be unique.
		$entityPath = array(
			'entities',
			$this->getUsekeys() ? $entityId->getPrefixedId() : $entityId->getNumericId()
		);

		// later we do a getContent but only if props are defined
		if ( $params['props'] !== array() ) {
			$page = $entityContentFactory->getWikiPageForId( $entityId );

			if ( $page->exists() ) {

				// as long as getWikiPageForId only returns ids for legal items this holds
				/**
				 * @var $entityContent \Wikibase\EntityContent
				 */
				$entityContent = $page->getContent();

				// this should not happen unless a page is not what we assume it to be
				// that is, we want this to be a little more solid if something ges wrong
				if ( is_null( $entityContent ) ) {
					$res->addValue( $entityPath, 'id', $entityId->getPrefixedId() );
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
						$res->addValue( $entityPath, 'modified', wfTimestamp( TS_ISO_8601, $revision->getTimestamp() ) );
					}
				}

				$entity = $entityContent->getEntity();

				// TODO: inject id formatter
				$options = new EntitySerializationOptions( WikibaseRepo::getDefaultInstance()->getIdFormatter() );
				$options->setLanguages( $params['languages'] );
				$options->setSortDirection( $params['dir'] );
				$options->setProps( $props );
				$options->setIndexTags( $this->getResult()->getIsRawMode() );

				$serializerFactory = new SerializerFactory();
				$entitySerializer = $serializerFactory->newSerializerForObject( $entity, $options );

				$entitySerialization = $entitySerializer->getSerialized( $entity );

				foreach ( $entitySerialization as $key => $value ) {
					$res->addValue( $entityPath, $key, $value );
				}
			}
			else {
				$res->addValue( $entityPath, 'missing', "" );
			}
		} else {
			$res->addValue( $entityPath, 'id', $entityId->getPrefixedId() );
			$res->addValue( $entityPath, 'type', $entityId->getEntityType() );
		}
		wfProfileOut( __METHOD__ );
	}

	/**
	 * @see \ApiBase::getAllowedParams()
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
				ApiBase::PARAM_ALLOW_DUPLICATES => true
			),
			'titles' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_ALLOW_DUPLICATES => true
			),
			'props' => array(
				ApiBase::PARAM_TYPE => array( 'info', 'sitelinks', 'aliases', 'labels',
					'descriptions', 'sitelinks/urls', 'claims', 'datatype' ),
				ApiBase::PARAM_DFLT => 'info|sitelinks|aliases|labels|descriptions|claims|datatype',
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
			'normalize' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			),
		) );
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'ids' => 'The IDs of the entities to get the data from',
			'sites' => array( 'Identifier for the site on which the corresponding page resides',
				"Use together with 'title', but only give one site for several titles or several sites for one title."
			),
			'titles' => array( 'The title of the corresponding page',
				"Use together with 'sites', but only give one site for several titles or several sites for one title."
			),
			'props' => array( 'The names of the properties to get back from each entity.',
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
			'normalize' => array( 'Try to normalize the page title against the client site.',
				'This only works if exactly one site and one page have been given.'
			),
		) );
	}

	/**
	 * @see \ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to get the data for multiple Wikibase entities.'
		);
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'param-missing', 'info' => $this->msg( 'wikibase-api-param-missing' )->text() ),
			array( 'code' => 'no-such-entity', 'info' => $this->msg( 'wikibase-api-no-such-entity' )->text() ),
			array(
				'code' => 'normalize-only-once',
				'info' => 'Normalize is only allowed if exactly one site and one page have been given'
			),
		) );
	}

	/**
	 * @see \ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			"api.php?action=wbgetentities&ids=q42"
			=> "Get entity with ID q42 with all available attributes in all available languages",
			"api.php?action=wbgetentities&ids=p2"
			=> "Get entity with ID p2 with all available attributes in all available languages",
			"api.php?action=wbgetentities&ids=q42|p2"
			=> "Get entities with IDs q42 and p2 with all available attributes in all available languages",
			"api.php?action=wbgetentities&ids=q42&languages=en"
			=> "Get entity with ID q42 with all available attributes in English language",
			"api.php?action=wbgetentities&ids=q42&props=labels"
			=> "Get entity with ID q42 showing all labels in all available languages",
			"api.php?action=wbgetentities&ids=p2|p3&props=datatype"
			=> "Get entities with IDs p2 and p3 showing only datatypes",
			"api.php?action=wbgetentities&ids=q42&props=aliases&languages=en"
			=> "Get entity with ID q42 showing all aliases in English language",
			"api.php?action=wbgetentities&ids=q1|q42&props=descriptions&languages=en|de|fr"
			=> "Get entities with IDs q1 and q42 showing descriptions in English, German and French languages",
			'api.php?action=wbgetentities&sites=enwiki&titles=Berlin&languages=en'
			=> 'Get the item for page "Berlin" on the site "enwiki", with language attributes in English language',
			'api.php?action=wbgetentities&sites=enwiki&titles=berlin&normalize='
			=> 'Get the item for page "Berlin" on the site "enwiki" after normalizing the title from "berlin"',
		);
	}

}
