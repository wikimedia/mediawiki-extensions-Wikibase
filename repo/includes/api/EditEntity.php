<?php

namespace Wikibase\Api;

use Wikibase\ChangeOps;

use Wikibase\ChangeOpLabel;
use Wikibase\ChangeOpDescription;
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
	 * @see  \Wikibase\Api\Api::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$type = $entity->getType();
		$permissions[] = 'edit';
		$permissions[] = $type . '-' . ( $entity->getId() === null ? 'create' : 'override' );
		return $permissions;
	}

	/**
	 * @see  \Wikibase\Api\ModifyEntity::findEntity()
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
		$status = Status::newGood();
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

		if ( isset( $params['data'] ) ) {
			$data = json_decode( $params['data'], true );
			if ( is_null( $data ) ) {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( $this->msg( 'wikibase-api-json-invalid' )->text(), 'json-invalid' );
			}
			if ( !is_array( $data ) ) { // NOTE: json_decode will decode any JS literal or structure, not just objects!
				wfProfileOut( __METHOD__ );
				$this->dieUsage( 'Top level structure must be a JSON object', 'not-recognized-array' );
			}
			$languages = array_flip( Utils::getLanguageCodes() );

			if ( isset( $params['clear'] ) && $params['clear'] ) {
				$entityContent->getEntity()->clear();
			}

			$title = null;
			$revision = null;
			$page = $entityContent->getWikiPage();
			if ( $page ) {
				$title = $page->getTitle();
				$revision = $page->getRevision();
			}

			foreach ( $data as $props => $list ) {
				if ( !is_string( $props ) ) { // NOTE: catch json_decode returning an indexed array (list)
					$this->dieUsage( 'Top level structure must be a JSON object', 'not-recognized-string' );
				}
				// unconditional no-ops
				if ( in_array( $props, array( 'length', 'count', 'touched' ) ) ) {
					continue;
				}
				// conditional no-ops
				if ( isset( $params['exclude'] ) && in_array( $props, $params['exclude'] ) ) {
					continue;
				}

				switch ($props) {

				// conditional processing
				case 'pageid':
					if ( isset( $data[$props] ) && ( is_object( $page ) ? $page->getId() !== $data[$props] : true ) ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'pageid' )->text(), 'illegal-field' );
					}
					break;
				case 'ns':
					// not completely convinced that we can use title to get the namespace in this case
					if ( isset( $data[$props] ) && ( is_object( $title ) ? $title->getNamespace() !== $data[$props] : true ) ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'namespace' )->text(), 'illegal-field' );
					}
					break;
				case 'title':
					if ( isset( $data[$props] ) && ( is_object( $title ) ? $title->getPrefixedText() !== $data[$props] : true ) ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'title' )->text(), 'illegal-field' );
					}
					break;
				case 'lastrevid':
					if ( isset( $data[$props] ) && ( is_object( $revision ) ? $revision->getId() !== $data[$props] : true ) ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'lastrevid' )->text(), 'illegal-field' );
					}
					break;

				// ordinary entries
				case 'labels':
					if ( !is_array( $list ) ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( "Key 'labels' must refer to an array", 'not-recognized-array' );
					}

					foreach ( $list as $langCode => $arg ) {
						$status->merge( $this->checkMultilangArgs( $arg, $langCode, $languages ) );

						$language = $arg['language'];
						$newLabel = Utils::trimToNFC( $arg['value'] );
						$oldLabel = $entity->getLabel( $language );

						if ( array_key_exists( 'remove', $arg ) || $newLabel === "" ) {
							$changeOps->add( new ChangeOpLabel( $language, null ) );
						}
						else {
							$changeOps->add( new ChangeOpLabel( $language, $newLabel ) );
						}
					}

					if ( !$status->isOk() ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( "Contained status: $1", $status->getWikiText() );
					}

					break;

				case 'descriptions':
					if ( !is_array( $list ) ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( "Key 'descriptions' must refer to an array", 'not-recognized-array' );
					}

					foreach ( $list as $langCode => $arg ) {
						$status->merge( $this->checkMultilangArgs( $arg, $langCode, $languages ) );

						$language = $arg['language'];
						$newDescription = Utils::trimToNFC( $arg['value'] );
						$oldDescription = $entity->getDescription( $language );

						if ( array_key_exists( 'remove', $arg ) || $newDescription === "" ) {
							$changeOps->add( new ChangeOpDescription( $language, null ) );
						}
						else {
							$changeOps->add( new ChangeOpDescription( $language, $newDescription ) );
						}
					}

					if ( !$status->isOk() ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( "Contained status: $1", $status->getWikiText() );
					}

					break;

				case 'aliases':
					if ( !is_array( $list ) ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( "Key 'aliases' must refer to an array", 'not-recognized-array' );
					}

					$aliases = array();

					foreach ( $list as $langCode => $arg ) {
						if ( intval( $langCode ) ) {
							$aliases[] = ( array_values($arg) === $arg ) ? $arg : array( $arg );
						} else {
							$aliases[$langCode] = ( array_values($arg) === $arg ) ? $arg : array( $arg );
						}
					}

					$setAliases = array();
					$addAliases = array();
					$remAliases = array();

					foreach ( $aliases as $langCode => $args ) {
						foreach ( $args as $arg ) {
							$status->merge( $this->checkMultilangArgs( $arg, $langCode, $languages ) );
							if ( array_key_exists( 'remove', $arg ) ) {
								$remAliases[$arg['language']][] = Utils::trimToNFC( $arg['value'] );
							}
							elseif ( array_key_exists( 'add', $arg ) ) {
								$addAliases[$arg['language']][] = Utils::trimToNFC( $arg['value'] );
							}
							else {
								$setAliases[$arg['language']][] = Utils::trimToNFC( $arg['value'] );
							}
						}
					}
					foreach ( $setAliases as $langCode => $strings ) {
							$entityContent->getEntity()->setAliases( $langCode, $strings );
					}
					foreach ( $remAliases as $langCode => $strings ) {
							$entityContent->getEntity()->removeAliases( $langCode, $strings );
					}
					foreach ( $addAliases as $langCode => $strings ) {
							$entityContent->getEntity()->addAliases( $langCode, $strings );
					}

					if ( !$status->isOk() ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( "Contained status: $1", $status->getWikiText() );
					}

					unset( $aliases );
					unset( $setAliases );
					unset( $addAliases );
					unset( $remAliases );

					break;

				case 'sitelinks':
					// TODO: This is a temporary fix that should be handled properly with an
					// injector/inputter class that is specific for the given entity
					if ( $entityContent->getEntity()->getType() !== Item::ENTITY_TYPE ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( "key can't be handled: $props", 'not-recognized' );
					}

					if ( !is_array( $list ) ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( "Key 'sitelinks' must refer to an array", 'not-recognized-array' );
					}

					$sites = $this->getSiteLinkTargetSites();

					foreach ( $list as $siteId => $arg ) {
						$status->merge( $this->checkSiteLinks( $arg, $siteId, $sites ) );
						if ( array_key_exists( 'remove', $arg ) || $arg['title'] === "" ) {
							$entityContent->getEntity()->removeSiteLink( $arg['site'] );
						}
						else {
							$linkSite = $sites->getSite( $arg['site'] );
							$linkPage = $linkSite->normalizePageName( Utils::trimWhitespace( $arg['title'] ) );

							if ( $linkPage === false ) {
								wfProfileOut( __METHOD__ );
								$this->dieUsage( $this->msg( 'wikibase-api-no-external-page' )->text(), 'add-sitelink-failed' );
							}

							$link = new SiteLink( $linkSite, $linkPage );
							$ret = $entityContent->getEntity()->addSiteLink( $link, 'set' );

							if ( $ret === false ) {
								wfProfileOut( __METHOD__ );
								$this->dieUsage( $this->msg( 'wikibase-api-add-sitelink-failed' )->text(), 'add-sitelink-failed' );
							}

							unset( $linkSite );
							unset( $linkPage );
							unset( $link );
							unset( $ret );
						}
					}

					if ( !$status->isOk() ) {
						wfProfileOut( __METHOD__ );
						$this->dieUsage( "Contained status: $1", $status->getWikiText() );
					}

					unset( $sites );

					break;

				default:
					wfProfileOut( __METHOD__ );
					$this->dieUsage( "unknown key: $props", 'not-recognized' );
				}
			}
		}

		$changeOps->apply( $entity );

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
	 * @param &$languages array: The valid language codes as an assoc array
	 *
	 * @return Status: The result from the comparison (always true)
	 */
	public function checkMultilangArgs( $arg, $langCode, &$languages = null ) {
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
		if ( isset( $languages ) && !array_key_exists( $arg['language'], $languages ) ) {
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
