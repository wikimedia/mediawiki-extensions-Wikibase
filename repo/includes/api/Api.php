<?php

namespace Wikibase;
use User, Status, ApiBase;

/**
 * Base class for API modules modifying a single item identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
abstract class Api extends \ApiBase {

	/**
	 * Figure out the instance-specific usekeys-state
	 *
	 * @deprecated
	 *
	 * @return bool true if the keys should be present
	 */
	protected function getUsekeys() {
		return !$this->getResult()->getIsRawMode();
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array(
			array( 'code' => 'jsonp-token-violation', 'info' => $this->msg( 'wikibase-api-jsonp-token-violation' )->text() ),
			array( 'code' => 'no-such-entity-revision', 'info' => 'The given revision ID doesn\'t exist in the specified entity.'  ),
			array( 'code' => 'cant-load-entity-content', 'info' => 'Can\'t load the content of the given page or revision.'  ),
		);
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array(
		);
	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array(
		);
	}

	/**
	 * Add aliases to result
	 *
	 * @deprecated
	 * TODO: remove, now in EntitySerializer
	 *
	 * @since 0.1
	 *
	 * @param array $aliases the aliases to set in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 * @return array|bool
	 */
	protected function addAliasesToResult( array $aliases, $path, $name = 'aliases', $tag = 'alias' ) {
		$value = array();

		if ( $this->getUsekeys() ) {
			foreach ( $aliases as $languageCode => $alarr ) {
				$arr = array();
				foreach ( $alarr as $alias ) {
					$arr[] = array(
						'language' => $languageCode,
						'value' => $alias,
					);
				}
				$value[$languageCode] = $arr;
			}
		}
		else {
			foreach ( $aliases as $languageCode => $alarr ) {
				foreach ( $alarr as $alias ) {
					$value[] = array(
						'language' => $languageCode,
						'value' => $alias,
					);
				}
			}
		}

		if ( $value !== array() ) {
			if ( !$this->getUsekeys() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}
			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Add sitelinks to result
	 *
	 * @deprecated
	 * TODO: move to EntitySerializer
	 *
	 * @since 0.1
	 *
	 * @param array $siteLinks the site links to insert in the result, as SiteLink objects
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 * @param array $options additional information to include in the listelinks structure. For example:
	 *              * 'url' will include the full URL of the sitelink in the result
	 *              * 'removed' will mark the sitelinks as removed
	 *              * other options will simply be included as flags.
	 *
	 * @return array|bool
	 */
	protected function addSiteLinksToResult( array $siteLinks, $path, $name = 'sitelinks', $tag = 'sitelink', $options = null ) {
		$value = array();
		$idx = 0;

		if ( isset( $options ) ) {
			// figure out if the entries shall be sorted
			$dir = null;
			if ( in_array( 'ascending', $options ) ) {
				$dir = 'ascending';
			}
			elseif ( in_array( 'descending', $options ) ) {
				$dir = 'descending';
			}

			if ( isset( $dir ) ) {
				// Sort the sitelinks according to their global id
				$saftyCopy = $siteLinks; // keep a shallow copy;

				$sortOk = false;

				if ( $dir === 'ascending' ) {
					$sortOk = usort(
						$siteLinks,
						function( $a, $b ) {
							/**
							 * @var SiteLink $a
							 * @var SiteLink $b
							 */
							return strcmp( $a->getSite()->getGlobalId(), $b->getSite()->getGlobalId() );
						}
					);
				} elseif ( $dir === 'descending' ) {
					$sortOk = usort(
						$siteLinks,
						function( $a, $b ) {
							/**
							 * @var SiteLink $a
							 * @var SiteLink $b
							 */
							return strcmp( $b->getSite()->getGlobalId(), $a->getSite()->getGlobalId() );
						}
					);
				}

				if ( !$sortOk ) {
					$siteLinks = $saftyCopy;
				}
			}
		}

		/**
		 * @var SiteLink $link
		 */
		foreach ( $siteLinks as $link ) {
			$response = array(
				'site' => $link->getSite()->getGlobalId(),
				'title' => $link->getPage(),
			);

			if ( $options !== null ) {
				foreach ( $options as $opt ) {
					if ( isset( $response[$opt] ) ) {
						//skip
					} elseif ( $opt === 'url' ) {
						//include full url in the result
						$response['url'] = $link->getUrl();
					} else {
						//include some flag in the result
						$response[$opt] = '';
					}
				}
			}

			$key = $this->getUsekeys() ? $link->getSite()->getGlobalId() : $idx++;
			$value[$key] = $response;
		}

		if ( $value !== array() ) {
			if ( !$this->getUsekeys() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}

			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Add descriptions to result
	 *
	 * @deprecated
	 * TODO: remove, now in EntitySerializer
	 *
	 * @since 0.1
	 *
	 * @param array $descriptions the descriptions to insert in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 * @return array|bool
	 */
	protected function addDescriptionsToResult( array $descriptions, $path, $name = 'descriptions', $tag = 'description' ) {
		$value = array();
		$idx = 0;

		foreach ( $descriptions as $languageCode => $description ) {
			if ( $description === '' ) {
				$value[$this->getUsekeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->getUsekeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $description,
				);
			}
		}

		if ( $value !== array() ) {
			if ( !$this->getUsekeys() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}

			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Add labels to result
	 *
	 * @deprecated
	 * TODO: remove, now in EntitySerializer
	 *
	 * @since 0.1
	 *
	 * @param array $labels the labels to set in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 * @return array|bool
	 */
	protected function addLabelsToResult( array $labels, $path, $name = 'labels', $tag = 'label' ) {
		$value = array();
		$idx = 0;

		foreach ( $labels as $languageCode => $label ) {
			if ( $label === '' ) {
				$value[$this->getUsekeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->getUsekeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $label,
				);
			}
		}

		if ( $value !== array() ) {
			if ( !$this->getUsekeys() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}

			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Returns the permissions that are required to perform the operation specified by
	 * the parameters.
	 *
	 * @param $entity Entity the entity to check permissions for
	 * @param $params array of arguments for the module, describing the operation to be performed
	 *
	 * @return \Status the check's result
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = array( 'read' );

		//could directly check for each module here:
		//$modulePermission = $this->getModuleName();
		//$permissions[] = $modulePermission;

		return $permissions;
	}

	/**
	 * Check the rights for the user accessing the module.
	 *
	 * @param $entityContent EntityContent the entity to check
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 *
	 * @return Status the check's result
	 * @todo: use this also to check for read access in ApiGetEntities, etc
	 */
	public function checkPermissions( EntityContent $entityContent, User $user, array $params ) {
		if ( Settings::get( 'apiInDebug' ) && !Settings::get( 'apiDebugWithRights', false ) ) {
			return Status::newGood();
		}

		$permissions = $this->getRequiredPermissions( $entityContent->getEntity(), $params );
		$status = Status::newGood();

		foreach ( $permissions as $perm ) {
			$permStatus = $entityContent->checkPermission( $perm, $user, true );
			$status->merge( $permStatus );
		}

		return $status;
	}

	/**
	 * Returns the list of sites that is suitable as a sitelink target.
	 *
	 * @return \SiteList
	 */
	protected function getSiteLinkTargetSites() {
		return \SitesTable::newInstance()->getSites()->getGroup( Settings::get( 'siteLinkGroup' ) );
	}


	/**
	 * Load the entity content of the given revision.
	 *
	 * Will fail by calling dieUsage() if the revision can not be found or can not be loaded.
	 *
	 * @since 0.3
	 *
	 * @param \Title   $title   : the title of the page to load the revision for
	 * @param bool|int $revId   : the revision to load. If not given, the current revision will be loaded.
	 * @param int      $audience
	 * @param \User    $user
	 * @param int      $audience: the audience to load this for, see Revision::FOR_XXX constants and
	 *                          Revision::getContent().
	 * @param \User    $user    : the user to consider if $audience == Revision::FOR_THIS_USER
	 *
	 * @return EntityContent|null the revision's content, or null if not available.
	 */
	protected function loadEntityContent( \Title $title, $revId = false,
		$audience = \Revision::FOR_PUBLIC,
		\User $user = null
	) {
		if ( $revId === null || $revId === false || $revId === 0 ) {
			$page = \WikiPage::factory( $title );
			$content = $page->getContent( $audience, $user );
		} else {
			$revision = \Revision::newFromId( $revId );

			if ( !$revision ) {
				$this->dieUsage( "Revision not found: $revId", 'no-such-entity-revision' );
			}

			if ( $revision->getPage() != $title->getArticleID() ) {
				$this->dieUsage( "Revision $revId does not belong to " .
					$title->getPrefixedDBkey(), 'no-such-entity-revision' );
			}

			$content = $revision->getContent( $audience, $user );
		}

		if ( is_null( $content ) ) {
			$this->dieUsage( "Can't access item content of " .
				$title->getPrefixedDBkey() .
				", revision may have been deleted.", 'cant-load-entity-content' );
		}

		return $content;
	}
}
