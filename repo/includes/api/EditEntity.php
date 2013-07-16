<?php

namespace Wikibase\Api;

use Wikibase\EntityContentFactory;
use InvalidArgumentException;
use Wikibase\ChangeOps;
use Wikibase\ChangeOpLabel;
use Wikibase\ChangeOpDescription;
use Wikibase\ChangeOpAliases;
use Wikibase\ChangeOpSiteLink;
use ApiBase, User, Status, SiteList;
use Wikibase\SiteLink;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\QueryContent;
use Wikibase\Autocomment;
use Wikibase\Utils;

/**
 * Derived class for API modules modifying a single entity identified by id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file ApiWikibaseModifyEntity.php
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class EditEntity extends ModifyEntity {

	/**
	 * @since 0.4
	 *
	 * @var string[]
	 */
	protected $validLanguageCodes;

	/**
	 * @see ApiBase::_construct()
	 */
	public function __construct( $mainModule, $moduleName ) {
		parent::__construct( $mainModule, $moduleName );

		$this->validLanguageCodes = array_flip( Utils::getLanguageCodes() );
	}

	/**
	 * @see \Wikibase\Api\Api::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$type = $entity->getType();
		$permissions[] = 'edit';
		$permissions[] = $type . '-' . ( $entity->getId() === null ? 'create' : 'override' );
		return $permissions;
	}

	/**
	 * @see ApiModifyEntity::createEntity()
	 */
	protected function createEntity( array $params ) {
		if ( !isset( $params['new'] ) ) {
			$this->dieUsage( "Either 'id' or 'new' parameter has to be set", 'nosuchentity' );
		}

		$type = $params['new'];
		$this->flags |= EDIT_NEW;
		$entityContentFactory = EntityContentFactory::singleton();
		try {
			return $entityContentFactory->newFromType( $type );
		} catch ( InvalidArgumentException $e ) {
			$this->dieUsage( "No such entity type: '$type'", 'nosuchentitytype' );
		}
	}

	/**
	 * @see \Wikibase\Api\ModifyEntity::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		// note that this is changed back and could fail
		if ( !( isset( $params['data'] ) OR  isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
			$this->dieUsage( 'Either provide the item "id" or pairs of "site" and "title" for a corresponding page, or "data" for a new item', 'missingparam' );
		}
		if ( isset( $params['id'] ) && isset( $params['new'] ) ) {
			$this->dieUsage( "Parameter 'id' and 'new' are not allowed to be both set in the same request", 'invalidparam' );
		}
		if ( !isset( $params['id'] ) && !isset( $params['new'] ) ) {
			$this->dieUsage( "Either 'id' or 'new' parameter has to be set", 'nosuchentity' );
		}
	}

	/**
	 * @see \Wikibase\Api\ModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( EntityContent &$entityContent, array $params ) {
		wfProfileIn( __METHOD__ );
		$summary = $this->createSummary( $params );
		$entity = $entityContent->getEntity();
		$changeOps = new ChangeOps();
		$status = Status::newGood();

		if ( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) {
			$summary->setAction( $params['clear'] === false ? 'update' : 'override' );
		}
		else {
			$summary->setAction( 'create' );
		}

		//TODO: Construct a nice and meaningful summary from the changes that get applied!
		//      Perhaps that could be based on the resulting diff?

		if ( !isset( $params['data'] ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'No data to operate upon', 'nodata' );
		}

		$data = json_decode( $params['data'], true );
		$status->merge( $this->checkDataProperties( $data, $entityContent->getWikiPage() ) );

		if ( $params['clear'] ) {
			$entityContent->getEntity()->clear();
		}

		// if we create a new property, make sure we set the datatype
		if ( $entityContent->isNew() && $entity->getType() === Property::ENTITY_TYPE ) {
			if ( !isset( $data['datatype'] ) ) {
				$this->dieUsage( 'No datatype given', 'invalidparam' );
			} else {
				$entity->setDataTypeId( $data['datatype'] );
			}
		}

		if ( array_key_exists( 'labels', $data ) ) {
			$changeOps->add( $this->getLabelChangeOps( $data['labels'], $status ) );
		}

		if ( array_key_exists( 'descriptions', $data ) ) {
			$changeOps->add( $this->getDescriptionChangeOps( $data['descriptions'], $status ) );
		}

		if ( array_key_exists( 'aliases', $data ) ) {
			$changeOps->add( $this->getAliasesChangeOps( $data['aliases'], $status ) );
		}

		if ( array_key_exists( 'sitelinks', $data ) ) {
			if ( $entity->getType() !== Item::ENTITY_TYPE ) {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( "key can't be handled: sitelinks", 'notrecognized' );
			}

			$changeOps->add( $this->getSiteLinksChangeOps( $data['sitelinks'], $status ) );
		}

		if ( !$status->isOk() ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "Edit failed: $1", 'failedsave' );
		}

		if ( $changeOps->apply( $entity ) === false ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Change could not be applied to entity', 'failedsave' );
		}

		$this->addLabelsToResult( $entity->getLabels(), 'entity' );
		$this->addDescriptionsToResult( $entity->getDescriptions(), 'entity' );
		$this->addAliasesToResult( $entity->getAllAliases(), 'entity' );

		// TODO: This is a temporary fix that should be handled properly with a
		// serializer class that is specific for the given entity
		if ( $entity->getType() === Item::ENTITY_TYPE ) {
			$this->addSiteLinksToResult( $entity->getSimpleSiteLinks(), 'entity' );
		}

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $labels
	 * @param Status $status
	 *
	 * @return ChangeOpLabel[]
	 */
	protected function getLabelChangeOps( $labels, Status $status ) {
		$labelChangeOps = array();

		if ( !is_array( $labels ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "List of labels must be an array", 'notrecognizedarray' );
		}

		foreach ( $labels as $langCode => $arg ) {
			$status->merge( $this->checkMultilangArgs( $arg, $langCode ) );

			$language = $arg['language'];
			$newLabel = $this->stringNormalizer->trimToNFC( $arg['value'] );

			if ( array_key_exists( 'remove', $arg ) || $newLabel === "" ) {
				$labelChangeOps[] = new ChangeOpLabel( $language, null );
			}
			else {
				$labelChangeOps[] = new ChangeOpLabel( $language, $newLabel );
			}
		}

		return $labelChangeOps;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $descriptions
	 * @param Status $status
	 *
	 * @return ChangeOpdescription[]
	 */
	protected function getDescriptionChangeOps( $descriptions, Status $status ) {
		$descriptionChangeOps = array();

		if ( !is_array( $descriptions ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "List of descriptions must be an array", 'notrecognizedarray' );
		}

		foreach ( $descriptions as $langCode => $arg ) {
			$status->merge( $this->checkMultilangArgs( $arg, $langCode ) );

			$language = $arg['language'];
			$newDescription = $this->stringNormalizer->trimToNFC( $arg['value'] );

			if ( array_key_exists( 'remove', $arg ) || $newDescription === "" ) {
				$descriptionChangeOps[] = new ChangeOpDescription( $language, null );
			}
			else {
				$descriptionChangeOps[] = new ChangeOpDescription( $language, $newDescription );
			}
		}

		return $descriptionChangeOps;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $aliases
	 * @param Status $status
	 *
	 * @return ChangeOpAliases[]
	 */
	protected function getAliasesChangeOps( $aliases, Status $status ) {
		$aliasesChangeOps = array();

		if ( !is_array( $aliases ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "List of aliases must be an array", 'notrecognizedarray' );
		}

		$indexedAliases = array();

		foreach ( $aliases as $langCode => $arg ) {
			if ( intval( $langCode ) ) {
				$indexedAliases[] = ( array_values($arg) === $arg ) ? $arg : array( $arg );
			} else {
				$indexedAliases[$langCode] = ( array_values($arg) === $arg ) ? $arg : array( $arg );
			}
		}

		foreach ( $indexedAliases as $langCode => $args ) {
			$aliasesToSet = array();

			foreach ( $args as $arg ) {
				$status->merge( $this->checkMultilangArgs( $arg, $langCode ) );

				$alias = array( $this->stringNormalizer->trimToNFC( $arg['value'] ) );
				$language = $arg['language'];

				if ( array_key_exists( 'remove', $arg ) ) {
					$aliasesChangeOps[] = new ChangeOpAliases( $language, $alias, 'remove' );
				}
				elseif ( array_key_exists( 'add', $arg ) ) {
					$aliasesChangeOps[] = new ChangeOpAliases( $language, $alias, 'add' );
				}
				else {
					$aliasesToSet[] = $alias[0];
				}
			}

			if ( $aliasesToSet !== array() ) {
				$aliasesChangeOps[] = new ChangeOpAliases( $language, $aliasesToSet, 'set' );
			}
		}

		if ( !$status->isOk() ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "Contained status: $1", $status->getWikiText() );
		}

		return $aliasesChangeOps;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $siteLinks
	 * @param Status $status
	 *
	 * @return ChangeOpSiteLink[]
	 */
	protected function getSitelinksChangeOps( $siteLinks, Status $status ) {
		$siteLinksChangeOps = array();

		if ( !is_array( $siteLinks ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "List of sitelinks must be an array", 'notrecognizedarray' );
		}

		$sites = $this->getSiteLinkTargetSites();

		foreach ( $siteLinks as $siteId => $arg ) {
			$status->merge( $this->checkSiteLinks( $arg, $siteId, $sites ) );
			$globalSiteId = $arg['site'];

			if ( $sites->hasSite( $globalSiteId ) ) {
				$linkSite = $sites->getSite( $globalSiteId );
			} else {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( "There is no site for global site id '$globalSiteId'", 'nosuchsite' );
			}

			if ( array_key_exists( 'remove', $arg ) || $arg['title'] === "" ) {
				$siteLinksChangeOps[] = new ChangeOpSiteLink( $globalSiteId, null );
			} else {
				$linkPage = $linkSite->normalizePageName( $this->stringNormalizer->trimWhitespace( $arg['title'] ) );

				if ( $linkPage === false ) {
					wfProfileOut( __METHOD__ );
					$this->dieUsage( 'The external client site did not provide page information' , 'noexternalpage' );
				}

				$siteLinksChangeOps[] = new ChangeOpSiteLink( $globalSiteId, $linkPage );
			}
		}

		return $siteLinksChangeOps;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $data
	 * @param WikiPage|bool $page
	 *
	 * @return Status
	 */
	protected function checkDataProperties( $data, $page ) {
		$status = Status::newGood();

		$title = null;
		$revision = null;
		if ( $page ) {
			$title = $page->getTitle();
			$revision = $page->getRevision();
		}

		$allowedProps = array(
			'length',
			'count',
			'touched',
			'pageid',
			'ns',
			'title',
			'lastrevid',
			'labels',
			'descriptions',
			'aliases',
			'sitelinks',
			'datatype' );

		if ( is_null( $data ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Invalid json: The supplied JSON structure could not be parsed or recreated as a valid structure' , 'invalidjson' );
		}

		if ( !is_array( $data ) ) { // NOTE: json_decode will decode any JS literal or structure, not just objects!
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Top level structure must be a JSON object', 'notrecognizedarray' );
		}

		foreach ( $data as $prop => $args ) {
			if ( !is_string( $prop ) ) { // NOTE: catch json_decode returning an indexed array (list)
				$this->dieUsage( 'Top level structure must be a JSON object', 'notrecognizedstring' );
			}

			if ( !in_array( $prop, $allowedProps ) ) {
				$this->dieUsage( "unknown key: $prop", 'notrecognized' );
			}
		}

		// conditional processing
		if ( isset( $data['pageid'] ) && ( is_object( $page ) ? $page->getId() !== $data['pageid'] : true ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Illegal field used in call: pageid', 'invalidparam' );
		}
		// not completely convinced that we can use title to get the namespace in this case
		if ( isset( $data['ns'] ) && ( is_object( $title ) ? $title->getNamespace() !== $data['ns'] : true ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Illegal field used in call: namespace', 'invalidparam' );
		}
		if ( isset( $data['title'] ) && ( is_object( $title ) ? $title->getPrefixedText() !== $data['title'] : true ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Illegal field used in call: title', 'invalidparam' );
		}
		if ( isset( $data['lastrevid'] ) && ( is_object( $revision ) ? $revision->getId() !== $data['lastrevid'] : true ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Illegal field used in call: lastrevid', 'invalidparam' );
		}

		return $status;
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'nosuchentity', 'info' => $this->msg( 'wikibase-api-nosuchentity' )->text() ),
			array( 'code' => 'nosuchentitytype', 'info' => $this->msg( 'wikibase-api-nosuchentitytype' )->text() ),
			array( 'code' => 'nodata', 'info' => $this->msg( 'wikibase-api-nodata' )->text() ),
			array( 'code' => 'notrecognized', 'info' => $this->msg( 'wikibase-api-notrecognized' )->text() ),
			array( 'code' => 'notrecognizedarray', 'info' => $this->msg( 'wikibase-api-notrecognizedarray' )->text() ),
			array( 'code' => 'nosuchsite', 'info' => $this->msg( 'wikibase-api-nosuchsite' )->text() ),
			array( 'code' => 'noexternalpage', 'info' => $this->msg( 'wikibase-api-noexternalpage' )->text() ),
			array( 'code' => 'invalidjson', 'info' => $this->msg( 'wikibase-api-invalidjson' )->text() ),
			array( 'code' => 'notrecognizedstring', 'info' => $this->msg( 'wikibase-api-notrecognizedstring' )->text() ),
			array( 'code' => 'notrecognized', 'info' => $this->msg( 'wikibase-api-notrecognized' )->text() ),
			array( 'code' => 'invalidparam', 'info' => $this->msg( 'wikibase-api-invalidparam' )->text() ),
			array( 'code' => 'missingparam', 'info' => $this->msg( 'wikibase-api-missingparam' )->text() ),
			array( 'code' => 'inconsistentlanguage', 'info' => $this->msg( 'wikibase-api-inconsistentlanguage' )->text() ),
			array( 'code' => 'notrecognisedlanguage', 'info' => $this->msg( 'wikibase-notrecognisedlanguage' )->text() ),
			array( 'code' => 'inconsistentsite', 'info' => $this->msg( 'wikibase-api-inconsistentsite' )->text() ),
			array( 'code' => 'notrecognizedsite', 'info' => $this->msg( 'wikibase-api-notrecognizedsite' )->text() ),
			array( 'code' => 'failedsave', 'info' => $this->msg( 'wikibase-api-failedsave' )->text() ),
		) );
	}

	/**
	 * @see \ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			parent::getAllowedParamsForId(),
			parent::getAllowedParamsForSiteLink(),
			parent::getAllowedParamsForEntity(),
			array(
				'data' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
				'clear' => array(
					ApiBase::PARAM_TYPE => 'boolean',
					ApiBase::PARAM_DFLT => false
				),
				'new' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
			)
		);
	}

	/**
	 * @see \ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			parent::getParamDescriptionForId(),
			parent::getParamDescriptionForSiteLink(),
			parent::getParamDescriptionForEntity(),
			array(
				'data' => array( 'The serialized object that is used as the data source.',
					"A newly created entity will be assigned an 'id'."
				),
				'clear' => array( 'If set, the complete entity is emptied before proceeding.',
					'The entity will not be saved before it is filled with the "data", possibly with parts excluded.'
				),
				'new' => array( "If set, a new entity will be created.",
						"Set this to the type of the entity you want to create - currently 'item'|'property'.",
						"It is not allowed to have this set when 'id' is also set."
				),
			)
		);
	}

	/**
	 * @see \ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to create a single new Wikibase entity and modify it with serialised information.'
		);
	}

	/**
	 * @see \ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbeditentity&new=item&data={}'
			=> 'Create a new empty item, returns extended with the item structure',
			'api.php?action=wbeditentity&clear=true&id=q42&data={}'
			=> 'Clear item with id q42',
			'api.php?action=wbeditentity&new=item&data={"labels":{"de":{"language":"de","value":"de-value"},"en":{"language":"en","value":"en-value"}}}'
			=> 'Create a new item and set labels for de and en',
			'api.php?action=wbeditentity&new=property&data={"labels":{"en-gb":{"language":"en-gb","value":"Propertylabel"}},"descriptions":{"en-gb":{"language":"en-gb","value":"Propertydescription"}},"datatype":"string"}'
			=> 'Create a new property containing the json data, returns extended with the item structure',
			'api.php?action=wbeditentity&id=q42&data={"sitelinks":{"nowiki":"København","svwiki":"Köpenhamn"}}'
			=> 'Sets sitelinks for nowiki and svwiki, overwriting them if they already exist',
		);
	}

	/**
	 * Check some of the supplied data for multilang arg
	 *
	 * @param $arg Array: The argument array to verify
	 * @param $langCode string: The language code used in the value part
	 *
	 * @return Status: The result from the comparison (always true)
	 */
	public function checkMultilangArgs( $arg, $langCode ) {
		$status = Status::newGood();
		if ( !is_array( $arg ) ) {
			$this->dieUsage( 'An array was expected, but not found' , 'notrecognizedarray' );
		}
		if ( !is_string( $arg['language'] ) ) {
			$this->dieUsage( 'A string was expected, but not found' , 'notrecognizedstring' );
		}
		if ( !is_numeric( $langCode ) ) {
			if ( $langCode !== $arg['language'] ) {
				$this->dieUsage( "inconsistent language: {$langCode} is not equal to {$arg['language']}", 'inconsistentlanguage' );
			}
		}
		if ( isset( $this->validLanguageCodes ) && !array_key_exists( $arg['language'], $this->validLanguageCodes ) ) {
			$this->dieUsage( "unknown language: {$arg['language']}", 'notrecognizedlanguage' );
		}
		if ( !is_string( $arg['value'] ) ) {
			$this->dieUsage( 'A string was expected, but not found' , 'notrecognizedstring' );
		}
		return $status;
	}

	/**
	 * Check some of the supplied data for sitelink arg
	 *
	 * @param $arg Array: The argument array to verify
	 * @param $siteCode string: The site code used in the argument
	 * @param &$sites \SiteList: The valid site codes as an assoc array
	 *
	 * @return Status: Always a good status
	 */
	public function checkSiteLinks( $arg, $siteCode, SiteList &$sites = null ) {
		$status = Status::newGood();
		if ( !is_array( $arg ) ) {
			$this->dieUsage( 'An array was expected, but not found' , 'notrecognizedarray' );
		}
		if ( !is_string( $arg['site'] ) ) {
			$this->dieUsage( 'A string was expected, but not found' , 'notrecognizedstring' );
		}
		if ( !is_numeric( $siteCode ) ) {
			if ( $siteCode !== $arg['site'] ) {
				$this->dieUsage( "inconsistent site: {$siteCode} is not equal to {$arg['site']}", 'inconsistentsite' );
			}
		}
		if ( isset( $sites ) && !$sites->hasSite( $arg['site'] ) ) {
			$this->dieUsage( "unknown site: {$arg['site']}", 'notrecognizedsite' );
		}
		if ( !is_string( $arg['title'] ) ) {
			$this->dieUsage( 'A string was expected, but not found' , 'notrecognizedstring' );
		}
		return $status;
	}

}
