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
	 * No wrapper for single error messages
	 *
	 * @var bool
	 */
	protected static $shortErrorConextMessage = false;

	/**
	 * Default wrapper for single error messages
	 *
	 * @var bool
	 */
	protected static $longErrorConextMessage = false;

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
		return \SiteSQLStore::newInstance()->getSites()->getGroup( Settings::get( 'siteLinkGroup' ) );
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

	/**
	 * Handle a status object. If $status->isOK() returns false, this method will terminate via
	 * the a call to $this->dieUsage(). Details from the Status object will be included in the
	 * API call's output.
	 *
	 * @param \Status $status
	 * @param string  $errorCode
	 * @param array   $extradata
	 * @param int     $httpRespCode
	 */
	protected function handleStatus( Status $status, $errorCode, array $extradata = array(), $httpRespCode = 0 ) {
		wfProfileIn( __METHOD__ );

		$res = $this->getResult();
		$isError = ( !$status->isOK() || $httpRespCode >= 400 );

		// report all warnings and errors
		if ( $status->isGood() ) {
			$description = null;
		} else {
			$description = $status->getWikiText( self::$shortErrorConextMessage, self::$longErrorConextMessage );
		}

		$errors = $status->getErrorsByType( $isError ? 'error' : 'warning' );
		$messages = $this->compileStatusReport( $errors );

		if ( $messages ) {
			//NOTE: this doesn't work:
			//$html = $status->getHTML( self::$shortErrorConextMessage, self::$longErrorConextMessage );
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

	protected function messageToHtml( $text ) {
		if ( $text === null || $text === false || $text === '' ) {
			return $text;
		}

		$out = \MessageCache::singleton()->parse( $text, $this->getContext()->getTitle(), /*linestart*/true,
			/*interface*/true, $this->getContext()->getLanguage() );

		return $out->getText();
	}

	/**
	 * Utility method for adding a list of messages to the result object.
	 * Useful for error reporting.
	 *
	 * @param $errors array a list of errors, as returned by Status::getErrorsByType()
	 * @param $messages array an result structure to add to (optional)
	 *
	 * @return array a result structure containing the given messages.
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

	// Documentation for this exists on some branch, wait for it to be merged here.
	// Then, remember to update the docu for $summary to include Summary|string.
	protected function attemptSaveEntity( EntityContent $content, /* string or Summary */ $summary, $flags = 0 ) {
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

		if ( $summary instanceof Summary ) {
			$summary = $summary->toString();
		}

		$status = $editEntity->attemptSave(
			$summary,
			$flags,
			$token
		);

		if ( $editEntity->hasError( EditEntity::TOKEN_ERROR ) ) {
			$this->handleStatus( $status, 'session-failure' );
		}
		elseif ( $editEntity->hasError( EditEntity::EDIT_CONFLICT_ERROR ) ) {
			$this->handleStatus( $status, 'edit-conflict' );
		}
		else {
			$this->handleStatus( $status, 'save-failed' );
		}

		return $editEntity;
	}
}
