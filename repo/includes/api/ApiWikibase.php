<?php

namespace Wikibase\Api;

use User, Status, ApiBase;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\Settings;
use Wikibase\EditEntity;
use Wikibase\SiteLink;

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
abstract class ApiWikibase extends \ApiBase {

	/**
	 * Wrapper message for single errors
	 *
	 * @var bool|string
	 */
	protected static $shortErrorContextMessage = false;

	/**
	 * Wrapper message for multiple errors
	 *
	 * @var bool|string
	 */
	protected static $longErrorContextMessage = false;

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
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array(
			array( 'code' => 'jsonp-token-violation', 'info' => $this->msg( 'wikibase-api-jsonp-token-violation' )->text() ),
			array( 'code' => 'no-such-entity-revision', 'info' => 'The given revision ID doesn\'t exist in the specified entity.'  ),
			array( 'code' => 'cant-load-entity-content', 'info' => 'Can\'t load the content of the given page or revision.'  ),
		);
	}

	/**
	 * @see \ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array(
		);
	}

	/**
	 * @see \ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array(
		);
	}


	/**
	 * @see \ApiBase::needsToken()
	 */
	public function needsToken() {
		return $this->isWriteMode() && ( !Settings::get( 'apiInDebug' ) || Settings::get( 'apiDebugWithTokens' ) );
	}

	/**
	 * @see \ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return $this->isWriteMode() && ( !Settings::get( 'apiInDebug' ) || Settings::get( 'apiDebugWithPost' ) );
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getVersion() {
		return get_class( $this ) . '-' . WB_VERSION;
	}

	/**
	 * @see ApiBase::getHelpUrls()
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#' . $this->getModuleName();
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
	 * TODO: move to \Wikibase\EntitySerializer
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
						function( SimpleSiteLink $a, SimpleSiteLink $b ) {
							return strcmp( $a->getSiteId(), $b->getSiteId() );
						}
					);
				} elseif ( $dir === 'descending' ) {
					$sortOk = usort(
						$siteLinks,
						function( SimpleSiteLink $a, SimpleSiteLink $b ) {
							return strcmp( $b->getSiteId(), $a->getSiteId() );
						}
					);
				}

				if ( !$sortOk ) {
					$siteLinks = $saftyCopy;
				}
			}
		}

		/**
		 * @var SimpleSiteLink $link
		 */
		foreach ( $siteLinks as $link ) {
			$response = array(
				'site' => $link->getSiteId(),
				'title' => $link->getPageName(),
			);

			if ( $options !== null ) {
				foreach ( $options as $opt ) {
					if ( isset( $response[$opt] ) ) {
						//skip
					} elseif ( $opt === 'url' ) {
						//include full url in the result
						$site = \Sites::singleton()->getSite( $link->getSiteId() );

						if ( $site === null ) {
							$response['url'] = '';
						}
						else {
							$siteLink = new SiteLink(
								$site,
								$link->getPageName()
							);

							$response['url'] = $siteLink->getUrl();
						}
					} else {
						//include some flag in the result
						$response[$opt] = '';
					}
				}
			}

			$key = $this->getUsekeys() ? $link->getSiteId() : $idx++;
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
	 * TODO: remove, now in \Wikibase\EntitySerializer
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
	 * TODO: remove, now in \Wikibase\EntitySerializer
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
	 * @param $entity \Wikibase\Entity the entity to check permissions for
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
	 * @param $entityContent \Wikibase\EntityContent the entity to check
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
		$sites = new \SiteList();
		$groups = Settings::get( 'siteLinkGroups' );

		$allSites = \SiteSQLStore::newInstance()->getSites();

		/* @var \Site $site */
		foreach ( $allSites as $site ) {
			if ( in_array( $site->getGroup(), $groups ) ) {
				$sites->append( $site );
			}
		}

		return $sites;
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
	 * @return \Wikibase\EntityContent|null the revision's content, or null if not available.
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

	/**
	 * Signal errors and warnings from a save operation to the API call's output.
	 * This is much like handleStatus(), but specialized for Status objects returned by
	 * EditEntity::attemptSave(). In particular, the 'errorFlags' and 'errorCode' fields
	 * from the status value are used to determine the error code to return to the caller.
	 *
	 * @note: this function may or may not return normally, depending on whether
	 *        the status is fatal or not.
	 *
	 * @see handleStatus().
	 *
	 * @param \Status $status The status to report
	 */
	protected function handleSaveStatus( Status $status ) {
		$value = $status->getValue();
		$errorCode = null;

		if ( is_array( $value ) && isset( $value['errorCode'] ) ) {
			$errorCode = $value['errorCode'];
		} else {
			$editError = 0;

			if ( is_array( $value ) && isset( $value['errorFlags'] ) ) {
				$editError = $value['errorFlags'];
			}

			if ( ( $editError & EditEntity::TOKEN_ERROR ) > 0 ) {
				$errorCode = 'session-failure';
			} elseif ( ( $editError & EditEntity::EDIT_CONFLICT_ERROR ) > 0 ) {
				$errorCode = 'edit-conflict';
			} elseif ( ( $editError & EditEntity::ANY_ERROR ) > 0 ) {
				$errorCode = 'save-failed';
			}
		}

		//NOTE: will just add warnings or do nothing if there's no error
		$this->handleStatus( $status, $errorCode );
	}

	/**
	 * Include messages from a Status object in the API call's output.
	 *
	 * If $status->isOK() returns false, this method will terminate via the a call
	 * to $this->dieUsage().
	 *
	 * If $status->isOK() returns false, any errors from the $status object will be included
	 * in the error-section of the API response. Otherwise, any warnings from $status will be
	 * included in the warnings-section of the API response. In both cases, a messages-section
	 * is used that includes an HTML representation of the messages as well as a list of message
	 * keys and parameters, for client side rendering and localization.
	 *
	 * @param \Status $status The status to report
	 * @param string  $errorCode The API error code to use in case $status->isOK() returns false
	 * @param array   $extradata Additional data to include the the error report,
	 *                if $status->isOK() returns false
	 * @param int     $httpRespCode the HTTP response code to use in case
	 *                $status->isOK() returns false.
	 *
	 * @warning This is a temporary solution, pending a similar feature in MediaWiki core,
	 *          see bug 45843.
	 *
	 * @see \ApiBase::dieUsage()
	 */
	protected function handleStatus( Status $status, $errorCode, array $extradata = array(), $httpRespCode = 0 ) {
		wfProfileIn( __METHOD__ );

		$res = $this->getResult();
		$isError = !$status->isOK();

		// report all warnings and errors
		if ( $status->isGood() ) {
			$description = null;
		} else {
			$description = $status->getWikiText( self::$shortErrorContextMessage, self::$longErrorContextMessage );
		}

		$errors = $status->getErrorsByType( $isError ? 'error' : 'warning' );
		$messages = $this->compileStatusReport( $errors );

		if ( $messages ) {
			//NOTE: Status::getHTML() doesn't work, see bug 45844
			//$html = $status->getHTML( self::$shortErrorContextMessage, self::$longErrorContextMessage );

			// nasty workaround:
			$html = $this->messageToHtml( $description );
			$res->setContent( $messages, $html, 'html' );
		}

		if ( $isError ) {
			$res->setElement( $extradata, 'messages', $messages );

			wfProfileOut( __METHOD__ );
			$this->dieUsage( $description, $errorCode, $httpRespCode, $extradata );
		} elseif ( $messages ) {
			$res->disableSizeCheck();
			$res->addValue( array( 'warnings' ), 'messages', $messages, true );
			$res->enableSizeCheck();

			wfProfileOut( __METHOD__ );
		}
	}

	/**
	 * Converts the given wiki text to HTML, using the parser mode for interface messages.
	 *
	 * @note: this is only needed as a temporary workaround for bug 45844
	 *
	 * @param string|null|bool $text The wikitext to parse.
	 *
	 * @return string|null|bool HTML of the wikitext if $text is a string; $text if it's false or null
	 *
	 * @see \MessageCache::parse()
	 */
	protected function messageToHtml( $text ) {
		if ( $text === null || $text === false || $text === '' ) {
			return $text;
		}

		$out = \MessageCache::singleton()->parse( $text, $this->getContext()->getTitle(), /*linestart*/true,
			/*interface*/true, $this->getContext()->getLanguage() );

		return $out->getText();
	}

	/**
	 * Utility method for compiling a list of messages into a form suitable for use
	 * in an API result structure.
	 *
	 * The $errors parameters is a list of (error) messages. Each entry in that array
	 * represents on message; the message can be represented as:
	 *
	 * * a message key, as a string
	 * * an indexed array with the message key as the first element, and the remaining elements
	 *   acting as message parameters
	 * * an associative array with the following fields:
	 *   - message: the message key (as a string); may also be a Message object, see below for that.
	 *   - params: a list of parameters (optional)
	 *   - type: the type of message (warning or error) (optional)
	 * * an associative array like above, but containing a Message object in the 'message' field.
	 *   In that case, the 'params' field is ignored and the parameter list is taken from the
	 *   Message object.
	 *
	 * This provides support for message lists coming from Status::getErrorsByType() as well as
	 * Title::getUserPermissionsErrors() etc.
	 *
	 * @param $errors array a list of errors, as returned by Status::getErrorsByType()
	 * @param $messages array an result structure to add to (optional)
	 *
	 * @return array a result structure containing the messages from $errors as well as what
	 *         was already present in the $messages parameter.
	 */
	protected function compileStatusReport( $errors, $messages = array() ) {
		if ( !is_array($errors) || $errors === array() ) {
			return $messages;
		}

		$res = $this->getResult();

		foreach ( $errors as $m ) {
			$type = null;
			$name = null;
			$params = null;

			if ( is_string( $m ) ) {
				// it's a plain string containing a message key
				$name = $m;
			} elseif ( is_array( $m ) ) {
				if ( isset( $m[0]) ) {
					// it's an indexed array, the first entriy is the message key, the rest are paramters
					$name = $m[0];
					$params = array_slice( $m, 1 );
				} else{
					// it's an assoc array, find message key and params in fields.
					$type = isset( $m['type'] ) ? $m['type'] : null;
					$params = isset( $m['params'] ) ? $m['params'] : null;

					if( isset( $m['message'] ) ) {
						if ( $m['message'] instanceof \Message ) {
							// message object, handle below
							$m = $m['message']; // NOTE: this triggers the "$m is an object" case below!
						} else {
							// plain key and param list
							$name = strval( $m['message'] );
						}
					}
				}
			}

			if ( $m instanceof \Message ) { //NOTE: no elsif, since $m can be manipulated
				// a message object

				$name = $m->getKey();
				$params = $m->getParams();
			}

			if ( $name !== null ) {
				// got at least a name

				$row = array();

				$res->setElement( $row, 'name', $name );

				if ( $type !== null ) {
					$res->setElement( $row, 'type', $type );
				}

				if ( $params !== null && !empty( $params ) ) {
					$res->setElement( $row, 'parameters', $params );
					$res->setIndexedTagName( $row['parameters'], 'parameter' );
				}

				$messages[] = $row;
			}
		}

		$res->setIndexedTagName( $messages, 'message' );
		return $messages;
	}

	/**
	 * Attempts to save the new entity content, chile first checking for permissions,
	 * edit conflicts, etc. Saving is done via EditEntity::attemptSave().
	 *
	 * This method automatically takes into account several parameters:
	 * * 'bot' for setting the bot flag
	 * * 'baserevid' for determining the edit's base revision for conflict resolution
	 * * 'token' for the edit token
	 *
	 * If an error occurs, it is automatically reported and execution of the API module
	 * is terminated using a call to dieUsage() (via handleStatus()). If there were any
	 * warnings, they will automatically be included in the API call's output (again, via
	 * handleStatus()).
	 *
	 * @param EntityContent $content  The content to save
	 * @param String $summary    The edit summary
	 * @param int    $flags      The edit flags (see WikiPage::toEditContent)
	 *
	 * @return Status the status of the save operation, as returned by EditEntity::attemptSave()
	 * @see  EditEntity::attemptSave()
	 *
	 * @todo: this could be factored out into a controller-like class, but that controller
	 *        would still need knowledge of the API module to be useful. We'll put it here
	 *        for now pending further discussion.
	 */
	protected function attemptSaveEntity( EntityContent $content, $summary, $flags = 0 ) {
		$params = $this->extractRequestParams();
		$user = $this->getUser();

		$flags |= ( $user->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$baseRevisionId = $baseRevisionId > 0 ? $baseRevisionId : false;

		$editEntity = new EditEntity( $content, $user, $baseRevisionId, $this->getContext() );

		if ( !$this->needsToken() ) {
			// false disabled the token check
			$token = false;
		} else {
			// null fails the token check
			$token = isset( $params['token'] ) ? $params['token'] : null;
		}

		$status = $editEntity->attemptSave(
			$summary,
			$flags,
			$token
		);

		$this->handleSaveStatus( $status );
		return $status;
	}

	/**
	 * Adds the ID of the new revision from the Status object to the API result structure.
	 * The status value is expected to be structured in the way that EditEntity::attemptSave()
	 * resp WikiPage::doEditContent() do it: as an array, with the new revision object in the
	 * 'revision' field.
	 *
	 * If no revision is found the the Status object, this method does nothing.
	 *
	 * @see ApiResult::addValue()
	 *
	 * @param string|null|array  $path  Where in the result to put the revision idf
	 * @param string  $name   The name to use for the revision id in the result structure
	 * @param \Status $status The status to get the revision ID from.
	 */
	protected function addRevisionIdFromStatusToResult( $path, $name, Status $status ) {
		$statusValue = $status->getValue();

		/* @var \Revision $revision */
		$revision = isset( $statusValue['revision'] )
			? $statusValue['revision'] : null;

		if ( $revision ) {
			$this->getResult()->addValue(
				$path,
				$name,
				intval( $revision->getId() )
			);
		}

	}
}
