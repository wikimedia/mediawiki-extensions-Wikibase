<?php

namespace Wikibase\Api;

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
use Wikibase\ItemContent;
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
	 * @since 0.4
	 *
	 * @var Status
	 */
	protected $status;

	/**
	 * @see ApiBase::_construct()
	 */
	public function __construct( $mainModule, $moduleName ) {
		parent::__construct( $mainModule, $moduleName );

		$this->validLanguageCodes = array_flip( Utils::getLanguageCodes() );
		$this->status = Status::newGood();
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
	 * @see \Wikibase\Api\ModifyEntity::findEntity()
	 */
	protected function findEntity( array $params ) {
		$entityContent = parent::findEntity( $params );

		// If we found anything then check if it is of the correct base class
		if ( is_object( $entityContent ) && !( $entityContent instanceof EntityContent ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-wrong-class' )->text(), 'wrong-class' );
		}

		return $entityContent;
	}

	/**
	 * @see ApiModifyEntity::createEntity()
	 */
	protected function createEntity( array $params ) {
		if ( isset( $params['data'] ) ) {
			$this->flags |= EDIT_NEW;
			if ( isset($params['id']) ) {
				switch ( $params['data'] ) {
					case 'item':
						return ItemContent::newEmpty();
					case 'property':
						return \Wikibase\PropertyContent::newEmpty();
					case 'query':
						return \Wikibase\QueryContent::newEmpty();
					default:
						$this->dieUsage( $this->msg( 'wikibase-api-no-such-entity' )->text(), 'no-such-entity' );
				}
			}
			else {
				return ItemContent::newEmpty();
			}
		}
		$this->dieUsage( $this->msg( 'wikibase-api-no-such-entity' )->text(), 'no-such-entity' );
	}

	/**
	 * @see \Wikibase\Api\ModifyEntity::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		// note that this is changed back and could fail
		if ( !( isset( $params['data'] ) OR  isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-data-or-id-xor-wikititle' )->text(), 'data-or-id-xor-wikititle' );
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
			$this->dieUsage( $this->msg( 'wikibase-api-no-data' )->text(), 'no-data' );
		}

		$data = json_decode( $params['data'], true );
		$this->status->merge( $this->checkDataProperties( $data, $entityContent->getWikiPage() ) );

		if ( isset( $params['clear'] ) && $params['clear'] ) {
			$entityContent->getEntity()->clear();
		}

		if ( array_key_exists( 'labels', $data ) ) {
			$changeOps->add( $this->getLabelChangeOps( $data['labels'] ) );
		}

		if ( array_key_exists( 'descriptions', $data ) ) {
			$changeOps->add( $this->getDescriptionChangeOps( $data['descriptions'] ) );
		}

		if ( array_key_exists( 'aliases', $data ) ) {
			$changeOps->add( $this->getAliasesChangeOps( $data['aliases'] ) );
		}

		if ( array_key_exists( 'sitelinks', $data ) ) {
			if ( $entity->getType() !== Item::ENTITY_TYPE ) {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( "key can't be handled: $props", 'not-recognized' );
			}

			$changeOps->add( $this->getSiteLinksChangeOps( $data['sitelinks'] ) );
		}

		if ( !$this->status->isOk() ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "Contained status: $1", $this->status->getWikiText() );
		}

		if ( $changeOps->apply( $entity ) === false ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Change could not be applied to entity', 'edit-entity-apply-failed' );
		}

		// This is already done in createEntity
		if ( $entityContent->isNew() ) {
			// if the entity doesn't exist yet, create it
			$this->flags |= EDIT_NEW;
		}

		$entity = $entityContent->getEntity();
		$this->addLabelsToResult( $entity->getLabels(), 'entity' );
		$this->addDescriptionsToResult( $entity->getDescriptions(), 'entity' );
		$this->addAliasesToResult( $entity->getAllAliases(), 'entity' );

		// TODO: This is a temporary fix that should be handled properly with a
		// serializer class that is specific for the given entity
		if ( $entityContent->getEntity()->getType() === Item::ENTITY_TYPE ) {
			$this->addSiteLinksToResult( $entity->getSiteLinks(), 'entity' );
		}

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $labels
	 *
	 * @return ChangeOpLabel[]
	 */
	protected function getLabelChangeOps( $labels ) {
		$labelChangeOps = array();

		if ( !is_array( $labels ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "Key 'labels' must refer to an array", 'not-recognized-array' );
		}

		foreach ( $labels as $langCode => $arg ) {
			$this->status->merge( $this->checkMultilangArgs( $arg, $langCode ) );

			$language = $arg['language'];
			$newLabel = Utils::trimToNFC( $arg['value'] );

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
	 *
	 * @return ChangeOpdescription[]
	 */
	protected function getDescriptionChangeOps( $descriptions ) {
		$descriptionChangeOps = array();

		if ( !is_array( $descriptions ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "Key 'descriptions' must refer to an array", 'not-recognized-array' );
		}

		foreach ( $descriptions as $langCode => $arg ) {
			$this->status->merge( $this->checkMultilangArgs( $arg, $langCode ) );

			$language = $arg['language'];
			$newDescription = Utils::trimToNFC( $arg['value'] );

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
	 *
	 * @return ChangeOpAliases[]
	 */
	protected function getAliasesChangeOps( $aliases ) {
		$aliasesChangeOps = array();

		if ( !is_array( $aliases ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "Key 'aliases' must refer to an array", 'not-recognized-array' );
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
			foreach ( $args as $arg ) {
				$this->status->merge( $this->checkMultilangArgs( $arg, $langCode ) );

				$alias = array( Utils::trimToNFC( $arg['value'] ) );
				$language = $arg['language'];

				if ( array_key_exists( 'remove', $arg ) ) {
					$aliasesChangeOps[] = new ChangeOpAliases( $language, $alias, 'remove' );
				}
				elseif ( array_key_exists( 'add', $arg ) ) {
					$aliasesChangeOps[] = new ChangeOpAliases( $language, $alias, 'add' );
				}
				else {
					$aliasesChangeOps[] = new ChangeOpAliases( $language, $alias, 'set' );
				}
			}
		}

		if ( !$this->status->isOk() ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "Contained status: $1", $this->status->getWikiText() );
		}

		unset( $indexedAliases );

		return $aliasesChangeOps;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $siteLinks
	 *
	 * @return ChangeOpSiteLink[]
	 */
	protected function getSitelinksChangeOps( $siteLinks ) {
		$siteLinksChangeOps = array();

		if ( !is_array( $siteLinks ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "Key 'sitelinks' must refer to an array", 'not-recognized-array' );
		}

		$sites = $this->getSiteLinkTargetSites();

		foreach ( $siteLinks as $siteId => $arg ) {
			$this->status->merge( $this->checkSiteLinks( $arg, $siteId, $sites ) );

			$linkSite = $sites->getSite( $arg['site'] );

			if ( array_key_exists( 'remove', $arg ) || $arg['title'] === "" ) {
				$siteLinksChangeOps[] = new ChangeOpSiteLink( $linkSite, null );
			} else {
				$linkPage = $linkSite->normalizePageName( Utils::trimWhitespace( $arg['title'] ) );

				if ( $linkPage === false ) {
					wfProfileOut( __METHOD__ );
					$this->dieUsage( $this->msg( 'wikibase-api-no-external-page' )->text(), 'add-sitelink-failed' );
				}

				$siteLinksChangeOps[] = new ChangeOpSiteLink( $linkSite, $linkPage );
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
			'sitelinks' );

		if ( is_null( $data ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $this->msg( 'wikibase-api-json-invalid' )->text(), 'json-invalid' );
		}

		if ( !is_array( $data ) ) { // NOTE: json_decode will decode any JS literal or structure, not just objects!
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Top level structure must be a JSON object', 'not-recognized-array' );
		}

		foreach ( $data as $prop => $args ) {
			if ( !is_string( $prop ) ) { // NOTE: catch json_decode returning an indexed array (list)
				$this->dieUsage( 'Top level structure must be a JSON objectt', 'not-recognized-string' );
			}

			if ( !in_array( $prop, $allowedProps ) ) {
				$this->dieUsage( "unknown key: $prop", 'not-recognized' );
			}
		}

		// conditional processing
		if ( isset( $data['pageid'] ) && ( is_object( $page ) ? $page->getId() !== $data['pageid'] : true ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'pageid' )->text(), 'illegal-field' );
		}
		// not completely convinced that we can use title to get the namespace in this case
		if ( isset( $data['ns'] ) && ( is_object( $title ) ? $title->getNamespace() !== $data['ns'] : true ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'namespace' )->text(), 'illegal-field' );
		}
		if ( isset( $data['title'] ) && ( is_object( $title ) ? $title->getPrefixedText() !== $data['title'] : true ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'title' )->text(), 'illegal-field' );
		}
		if ( isset( $data['lastrevid'] ) && ( is_object( $revision ) ? $revision->getId() !== $data['lastrevid'] : true ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'lastrevid' )->text(), 'illegal-field' );
		}

		return $status;
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'no-data', 'info' => $this->msg( 'wikibase-api-no-data' )->text() ),
			array( 'code' => 'wrong-class', 'info' => $this->msg( 'wikibase-api-wrong-class' )->text() ),
			array( 'code' => 'cant-edit', 'info' => $this->msg( 'wikibase-api-cant-edit' )->text() ),
			array( 'code' => 'no-permissions', 'info' => $this->msg( 'wikibase-api-no-permissions' )->text() ),
			array( 'code' => 'save-failed', 'info' => $this->msg( 'wikibase-api-save-failed' )->text() ),
			array( 'code' => 'add-sitelink-failed', 'info' => $this->msg( 'wikibase-api-add-sitelink-failed' )->text() ),
			array( 'code' => 'illegal-field', 'info' => $this->msg( 'wikibase-api-illegal-field' )->text() ),
			array( 'code' => 'not-recognized', 'info' => $this->msg( 'wikibase-api-not-recognized' )->text() ),
			array( 'code' => 'not-recognized-string', 'info' => $this->msg( 'wikibase-api-not-recognized-string' )->text() ),
			array( 'code' => 'not-recognized-array', 'info' => $this->msg( 'wikibase-api-not-recognized-array' )->text() ),
			array( 'code' => 'inconsistent-language', 'info' => $this->msg( 'wikibase-api-inconsistent-language' )->text() ),
			array( 'code' => 'inconsistent-site', 'info' => $this->msg( 'wikibase-api-inconsistent-site' )->text() ),
			array( 'code' => 'inconsistent-values', 'info' => $this->msg( 'wikibase-api-inconsistent-values' )->text() )
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
				'exclude' => array(
					ApiBase::PARAM_TYPE => array( 'pageid', 'ns', 'title', 'lastrevid', 'touched', 'sitelinks', 'aliases', 'labels', 'descriptions' ),
					ApiBase::PARAM_DFLT => '',
					ApiBase::PARAM_ISMULTI => true,
				),
				'clear' => array(
					ApiBase::PARAM_TYPE => 'boolean',
					ApiBase::PARAM_DFLT => false
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
				'exclude' => array( 'List of substructures to neglect during the processing.',
				"In addition 'length', 'touched' and 'count' is always excluded."
				),
				'clear' => array( 'If set, the complete emptied is emptied before proceeding.',
				'The entity will not be saved before it is filled with the "data", possibly with parts excluded.'
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
			'api.php?action=wbeditentity&data={}&format=jsonfm'
			=> 'Set an empty JSON structure for the entity, it will be extended with an id and the structure cleansed and completed. Report it as pretty printed json format.',
			'api.php?action=wbeditentity&data={"labels":{"de":{"language":"de","value":"de-value"},"en":{"language":"en","value":"en-value"}}}'
			=> 'Set a more complete JSON structure for the entity, it will be extended with an id and the structure cleansed and completed.',
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
			$this->dieUsage( $this->msg( 'wikibase-api-not-recognized-array' )->text(), 'not-recognized-array' );
		}
		if ( !is_string( $arg['language'] ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
		}
		if ( !is_numeric( $langCode ) ) {
			if ( $langCode !== $arg['language'] ) {
				$this->dieUsage( "inconsistent language: {$langCode} is not equal to {$arg['language']}", 'inconsistent-language' );
			}
		}
		if ( isset( $this->validLanguageCodes ) && !array_key_exists( $arg['language'], $this->validLanguageCodes ) ) {
			$this->dieUsage( "unknown language: {$arg['language']}", 'not-recognized-language' );
		}
		if ( !is_string( $arg['value'] ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
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
			$this->dieUsage( $this->msg( 'wikibase-api-not-recognized-array' )->text(), 'not-recognized-array' );
		}
		if ( !is_string( $arg['site'] ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
		}
		if ( !is_numeric( $siteCode ) ) {
			if ( $siteCode !== $arg['site'] ) {
				$this->dieUsage( "inconsistent site: {$siteCode} is not equal to {$arg['site']}", 'inconsistent-site' );
			}
		}
		if ( isset( $sites ) && !$sites->hasSite( $arg['site'] ) ) {
			$this->dieUsage( "unknown site: {$arg['site']}", 'not-recognized-site' );
		}
		if ( !is_string( $arg['title'] ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
		}
		return $status;
	}

}
