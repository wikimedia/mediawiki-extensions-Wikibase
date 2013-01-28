<?php

namespace Wikibase;
/**
 * File defining the handler for autocomments and additional utility functions
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad
 */
class Summary {

	/**
	 * @var stringPropertyView
	 */
	protected $moduleName;

	/**
	 * @var string
	 */
	protected $actionName;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var array
	 */
	protected $commentArgs;

	/**
	 * @var array
	 */
	protected $summaryArgs;

	/**
	 * @var int
	 */
	protected $summaryNumArgs;

	/**
	 * Constructs a new Summary
	 *
	 * @since 0.3
	 *
	 * @param string $moduleName the module part of the autocomment
	 * @param string $actionName the action part of the autocomment
	 * @param Language $language the language to use for the autosummary (like list separators)
	 * @param array $commentArgs the arguments to the autocomment
	 * @param array $summaryArgs the arguments to the autosummary
	 */
	public function __construct( $moduleName = null, $actionName = null, Language $language = null, $commentArgs = array(), $summaryArgs = false ) {
		//global $wgContLang;

		$this->moduleName = $moduleName;
		$this->actionName = $actionName;
		$this->language = isset( $language ) ? $language : null;
		$this->commentArgs = $commentArgs;
		$this->summaryArgs = $summaryArgs;
		$this->summaryNumArgs = false;
	}

	/**
	 * Set the language for the summary part
	 * @param Language $lang
	 */
	public function setLanguage( \Language $lang = null ) {
		$this->language = $lang;
	}

	/**
	 * Set the module part of the autocomment
	 * @param string $name
	 */
	public function setModuleName( $name ) {
		$this->moduleName = (string)$name;
	}

	/**
	 * Get the module part of the autocomment
	 */
	public function getModuleName() {
		return $this->moduleName;
	}

	/**
	 * Set the action part of the autocomment
	 * @param string $name
	 */
	public function setAction( $name ) {
		$this->actionName = (string)$name;
	}

	/**
	 * Get the action part of the autocomment
	 */
	public function getActionName() {
		return $this->actionName;
	}

	/**
	 * Pretty formating of autocomments.
	 *
	 * TODO: this function needs refactoring
	 * TODO: this does not handle sublists in the arguments
	 *
	 * @param $data
	 * @param string $comment reference to the finalized autocomment
	 * @param string $pre the string before the autocomment
	 * @param string $auto the autocomment unformatted
	 * @param string $post the string after the autocomment
	 * @param \Title $title use for further information
	 * @param boolean $local shall links be generated locally or globally
	 *
	 * @return boolean
	 */
	public static function onFormat( $data, &$comment, $pre, $auto, $post, $title, $local ) {
		global $wgLang, $wgTitle, $wgUser;
		$audience = \Revision::FOR_THIS_USER;

		list( $model, $root ) = $data;

		// If it is possible to avoid loading the whole page then the code will be lighter on the server.
		$title = $title === null ? $wgTitle : $title;

		if ( $title->getContentModel() === $model ) {

			if ( preg_match( '/^([\-a-z]+?)\s*(:\s*(.*?))?\s*$/', $auto, $matches ) ) {

				// turn the args to the message into an array
				$args = ( 3 < count( $matches ) ) ? explode( '|', $matches[3] ) : array();
				// make the strings websafe, they can later fall through processing
				// this is somewhat hostile, it throws away everything that looks suspicious
				$args = array_map(
					function( $val ) { return preg_match('/[^\-\w\$]/', $val ) ? '' : $val; },
					$args
				);

				// look up the message
				$msg = wfMessage( $root . '-summary-' . $matches[1] );
				if ( !$msg->isDisabled() ) {
					$arrowLink = '';
					if ( isset( $args[1] ) ) {
						// TODO: this test is black magic, we need a better solution
						if ( preg_match( '/wiki$/', $args[1] ) ) {
							$arrowLink = static::makeArrowLink( $title, $args[1], $local );

							$page = new \WikiPage( $title );
							if ( $page !== null ) {
								$content = null;

								try {
									$content = $page->getContent();
								} catch ( \MWContentSerializationException $ex ) {
									wfWarn( "Failed to get entity object for [[" . $page->getTitle()->getFullText() . "]]"
											. ": " . $ex->getMessage() );
								}

								if ( $content !== null && ( $content instanceof ItemContent ) ) {
									$item = $content->getItem();
									if ( $item !== null ) {
										$sitelink = $item->getSiteLink( $args[1] );
										$externalLink = \Linker::makeExternalLink(
											$sitelink->getUrl(),
											Utils::fetchLanguageName( $sitelink->getSite()->getLanguageCode(), $wgLang->getCode() ),
											true,
											'',
											array(
												'title' => $sitelink->getPage()
											)
										);
									}
								}
							}
							if ( $externalLink !== null  ) {
								$args[1] = $externalLink;
							}
							else {
								$site = \SiteSQLStore::newInstance()->getSite( $args[1] );
								$args[1] = isset($site) ? Utils::fetchLanguageName( $site->getLanguageCode(), $wgLang->getCode() ) : $args[1];
							}
						}
						else {
							$args[1] = Utils::fetchLanguageName( $args[1], $wgLang->getCode() );
						}
					}

					// test if we should build the arrow link and if so possibly the
					// link that goes into the third argument too
					if ( isset( $args[2] ) ) {
						$arrowLink = static::makeArrowLink( $title, $args[2], $local );

						// TODO: this test is black magic, we need a better solution
						if ( strpos( $args[2], '$' ) !== false ) {
							$entityId = EntityId::newFromPrefixedId( Entity::getIdFromClaimGuid( $args[2] ) );
							$entityContent = EntityContentFactory::singleton()->getFromId( $entityId, $audience );

							if ( $entityContent !== null ) {
								$claims = $claims = new \Wikibase\Claims( $entityContent->getEntity()->getClaims() );

								if ( $claims->hasClaimWithGuid( $args[2] ) ) {
									$claim = $claims->getClaimWithGuid( $args[2] );
									$entityId = $claim->getMainSnak()->getPropertyId();
									$link = static::makeEntityLink( $entityId, $audience );
								}
							}
						}
						else {
							$entityId = EntityId::newFromPrefixedId( $args[2] );
							$link = static::makeEntityLink( $entityId, $audience );
						}
						$args[2] = isset( $link ) ? $link : $args[2];
					}
					// parse the autocomment
					$args = array_map(
						function( $val ) { return is_numeric( $val ) ? $val : \Message::rawParam( $val ); },
						$args
					);
					$auto = $msg->params( $args )->parse();

					// add pre and post fragments
					if ( $pre ) {
						// written summary $presep autocomment (summary /* section */)
						$pre .= wfMessage( 'autocomment-prefix' )->escaped();
					}
					if ( $post ) {
						// autocomment $postsep written summary (/* section */ summary)
						$auto .= wfMessage( 'colon-separator' )->escaped();
					}

					$auto = '<span class="autocomment">' . $auto . '</span>';
					$comment = $pre . $arrowLink . $wgLang->getDirMark() . '<span dir="auto">' . $auto . $post . '</span>';
				}
			}
		}
		return true;
	}

	/**
	 * Make a title object that has a fragment identifier
	 * 
	 * The fragment identifier will link to some part of an entity page.
	 *
	 * @since 0.4
	 *
	 * @param \Title $title
	 * @param $fragment
	 * @param $local
	 *
	 * @return string version of a link with a localized arrow as the link text
	 */
	private static function makeArrowLink( \Title $title, $fragment, $local = \Revision::FOR_PUBLIC ) {
		global $wgLang;

		$sectionText = \Sanitizer::normalizeSectionNameWhitespace( $fragment ); # bug 22784

		$sectionTitle = $local
			? \Title::newFromText( '#' . $sectionText ) // fragment might not work in all cases
			: \Title::makeTitleSafe( $title->getNamespace(), $title->getDBkey(), $sectionText );

		$arrowLink = \Linker::link( $sectionTitle, $wgLang->getArrow(), array(), array(), 'noclasses' );

		return $arrowLink;
	}

	/**
	 * Make a link to an entity of some kind given an entity id and an optional audience
	 *
	 * @since 0.4
	 *
	 * @params EntityId $entityId
	 * @params $audience
	 * 
	 * @return string version of a link with the entitys localized label as the link text
	 */
	private static function makeEntityLink( EntityId $entityId, $audience ) {
		global $wgLang;

		$entityContent = EntityContentFactory::singleton()->getFromId( $entityId, $audience );

		if ( $entityContent !== null ) {
			$entityTitle = $entityContent->getTitle();
			$entityLabel = $entityContent->getEntity()->getLabel( $wgLang->getCode() );
			$entityLink = \Linker::link(
				$entityTitle,
				( $entityLabel === null ? $entityTitle : $entityLabel),
				array(),
				array(),
				'noclasses'
			);
		}
		return $entityLink;
	}

	/**
	 * Pick values from a params array and collect them in a array
	 *
	 * This takes a call with a vararg list and reduce that list to the
	 * entries that has values in the params array, possibly also flattening
	 * any arrays.
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @param array|string... $sequence array or variant number of strings
	 * @return array of found items
	 */
	public static function pickValuesFromParams( array $params ) {

		$sequence = func_get_args();
		array_shift( $sequence );

		if ( 1 === count( $sequence ) && is_array( $sequence[0] ) ) {
			$sequence = $sequence[0];
		}

		$common = array_intersect_key( array_flip( $sequence ), $params );
		$filtered = array_merge( $common, array_intersect_key( $params, $common ) );

		$values = array();
		foreach ( $filtered as $v ) {
			if ( is_string( $v ) && $v !== '' ) {
				$values[] = $v;
			}
			elseif ( is_array( $v ) && $v !== array() ) {
				$values = array_merge( $values, $v );
			}
		}
		return array_unique( $values );
	}

	/**
	 * Pick keys from a params array and string them together
	 *
	 * This takes a call with a vararg list and reduce that list to the
	 * entries that is also keys in the params array.
	 *
	 * @since 0.1
	 *
	 * @param array $params parameters from the call to the containg module
	 * @param array|string... $sequence array or variant number of strings
	 * @return array of found items
	 */
	public static function pickKeysFromParams( array $params ) {
		$sequence = func_get_args();
		array_shift( $sequence );

		if ( 1 === count( $sequence ) && is_array( $sequence[0] ) ) {
			$sequence = $sequence[0];
		}

		$common = array_filter(
			$sequence,
			function( $key ) use ( $params ) { return isset( $params[$key] ); }
		);
		return $common;
	}

	/**
	 * Format the message key for an autocomment
	 *
	 * @since 0.3
	 *
	 * @param array $parts parts to be stringed together
	 *
	 * @return string with a message key, or possibly an empty string
	 */
	public static function formatMessageKey( array $parts ) {
		return implode('-', $parts);
	}

	/**
	 * Format the message key using the object-specific values
	 *
	 * @since 0.3
	 *
	 * @return string with a message key, or possibly an empty string
	 */
	public function getMessageKey() {
		return self::formatMessageKey(
			$this->actionName === null
				? array( $this->moduleName )
				: array( $this->moduleName, $this->actionName )
		);
	}

	/**
	 * Format the autocomment part of a full summary
	 *
	 * @since 0.1
	 *
	 * @param string $messageKey the message key
	 * @param array $parts parts to be stringed together
	 * @return string with a formatted comment, or possibly an empty string
	 */
	public static function formatAutoComment( $messageKey, array $parts ) {
		$joinedParts = implode( '|', $parts );
		$composite = ( 0 < strlen($joinedParts) )
			? implode( ':', array( $messageKey, $joinedParts ) )
			: $messageKey;
		return $composite;
	}

	/**
	 * Set the autocomment arguments using the object-specific values
	 *
	 * @since 0.3
	 *
	 * @param array|strings... parts to be stringed together
	 */
	public function addAutoCommentArgs( /*...*/ ) {
		$args = is_array( func_get_arg( 0 ) ) ? func_get_arg( 0 ) : func_get_args();
		$this->commentArgs = array_merge(
			$this->commentArgs,
			array_filter( $args, function ( $str ) { return 0 < strlen( $str ); } )
		);
	}

	/**
	 * Get the formatted autocomment using the object-specific values
	 *
	 * @since 0.3
	 *
	 * @return string with a formatted autocomment, or possibly an empty string
	 */
	public function getAutoComment() {
		return self::formatAutoComment( $this->getMessageKey(), $this->commentArgs );
	}

	/**
	 * Format the autosummary part of a full summary
	 *
	 * This creates a comma list of entries, and to make the comma form
	 * it is necessary to have a language. This can be a real problem as
	 * guessing it will often fail.
	 *
	 * @since 0.1
	 *
	 * @param array|int $parts parts to be stringed together
	 * @param \Language|null $lang fallback for the language if its not set
	 *
	 * @return array of counts, an escaped string and the used language
	 */
	public static function formatAutoSummary( $parts, $lang = null ) {
		global $wgContLang;

		if ( !isset( $lang ) ) {
			$lang = $wgContLang;
		}

		if ( $parts === false ) {
			$count = $parts;

			return array( 0, '', $lang );
		}
		elseif ( is_array( $parts ) ) {
			$count = count( $parts );

			if ( $count === 0 ) {
				return array( 0, '', $lang );
			}
			elseif ( $count === 1 ) {
				return array( 1, $parts[0], $lang );
			}
			else {
				$composite = $lang->commaList( $parts );
				return array( count( $parts ), $composite, $lang );
			}
		}
		elseif ( is_int( $parts ) ) {
			$count = $parts;

			return array( $count, '', $lang );
		}
		else {
			throw new \MWException( 'wrong type' );
		}
	}

	/**
	 * Set the autosummary arguments using the object-specific values
	 *
	 * @since 0.3
	 *
	 * @param array|strings... parts to be stringed together
	 */
	public function addAutoSummaryArgs( /*...*/ ) {
		$args = is_array( func_get_arg( 0 ) ) ? func_get_arg( 0 ) : func_get_args();

		$this->summaryArgs = array_merge(
			$this->summaryArgs === false ? array() : $this->summaryArgs,
			array_filter( $args, function ( $str ) { return 0 < strlen( $str ); } )
		);
	}

	/**
	 * Set the autosummary number of arguments using the object-specific values
	 *
	 * This will only accumulate in the counter and the array will not be stored.
	 * @since 0.3
	 *
	 * @param array|strings... parts to be stringed together
	 */
	public function addAutoSummaryNumArgs( /*...*/ ) {
		$args = is_array( func_get_arg( 0 ) ) ? func_get_arg( 0 ) : func_get_args();

		if ( $this->summaryNumArgs === false ) {
			$this->summaryNumArgs = 0;
		}
		$this->summaryNumArgs += count( $args );
	}

	/**
	 * Get the preformatted autosummary using the object-specific values
	 *
	 * @since 0.3
	 *
	 * @param array $parts parts to be stringed together
	 *
	 * @return array of counts, an escaped string and the used language
	 */
	public function getAutoSummary( array $parts ) {
		return self::formatAutoSummary( $this->summaryArgs, $this->language );
	}

	/**
	 * Merge the total summary
	 *
	 * @since 0.1
	 *
	 * @param string $comment initial part to go in a comment
	 * @param string $summary final part that is a easilly trucable string
	 * @param bool|string $lang language to use when truncating the string
	 * @param int $length total length of the string
	 *
	 * @return string to be used for the summary
	 */
	public static function formatTotalSummary( $comment, $summary, $lang = false, $length = SUMMARY_MAX_LENGTH ) {
		global $wgContLang;
		if ( $lang === null || $lang === false) {
			$lang = $wgContLang;
		}
		$comment = Utils::squashToNFC( $comment );
		$summary = Utils::squashToNFC( $summary );
		$mergedString = '';
		if ( $comment !== '' ) {
			$mergedString .=  "/* $comment */";
		}
		if ( $summary !== "" ) {
			$mergedString .= ($mergedString === "" ? "" : " ") . $lang->truncate( $summary, $length - strlen( $mergedString ) );
		}

		// leftover entities should be removed, but its not clear how this shall be done
		return $mergedString;
	}

	/**
	 * Merge the total summary using the object specific values
	 *
	 * @since 0.3
	 *
	 * @return string to be used for the summary
	 */
	public function toString( $length = SUMMARY_MAX_LENGTH ) {
		list( $counts, $summary, $lang) = self::formatAutoSummary(
			$this->summaryArgs === false ? $this->summaryNumArgs : $this->summaryArgs,
			$this->language
		);
		$comment = Summary::formatAutoComment(
			$this->getMessageKey(),
			array_merge(
				$this->language === null
					? array( $counts, '' )
					: array( $counts, $this->language->getCode() ),
				$this->commentArgs
			)
		);
		return self::formatTotalSummary( $comment, $summary, $lang );
	}

	/**
	 * Build the summary by call to the module
	 *
	 * If this is used for other classes than api modules it could be necessary to change
	 * its internal logic
	 *
	 * @since 0.1
	 *
	 * @param ApiSummary $module an api module that support ApiSummary
	 *
	 * @param null|array $params
	 * @param null|EntityContent $entityContent
	 * @return string to be used for the summary
	 */
	public static function buildApiSummary( $module, $params = null, $entityContent = null ) {
		// check if we must pull in the request params
		if ( !isset( $params ) ) {
			$params = $module->extractRequestParams();
		}

		// Is there a user supplied summary, then use it but get the hits first
		if ( isset( $params['summary'] ) ) {
			list( $hits, $summary, $lang ) = $module->getTextForSummary( $params );
			$summary = $params['summary'];
		}

		// otherwise try to construct something
		else {
			list( $hits, $summary, $lang ) = $module->getTextForSummary( $params );
			if ( !is_string( $summary ) ) {
				if ( isset( $entityContent ) ) {
					$summary = $entityContent->getTextForSummary( $params );
				}
				else {
					$summary = '';
				}
			}
		}

		// Comments are newer user supplied
		$comment = $module->getTextForComment( $params, $hits );

		// format the overall string and return it
		return Summary::formatTotalSummary( $comment, $summary, $lang );
	}
}
