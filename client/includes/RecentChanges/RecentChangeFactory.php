<?php

namespace Wikibase\Client\RecentChanges;

use Language;
use Message;
use MWException;
use RecentChange;
use Title;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class RecentChangeFactory {

	/**
	 * Classification of Wikibase changss in the rc_source column of the
	 * recentchanges table.
	 */
	const SRC_WIKIBASE = 'wb';

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var SiteLinkCommentCreator
	 */
	private $siteLinkCommentCreator;

	/**
	 * @param Language $language
	 * @param SiteLinkCommentCreator $siteLinkCommentCreator
	 */
	public function __construct( Language $language, SiteLinkCommentCreator $siteLinkCommentCreator ) {
		$this->language = $language;
		$this->siteLinkCommentCreator = $siteLinkCommentCreator;
	}

	/**
	 * Creates a local RecentChange object that corresponds to the EntityChange from the
	 * repo, with respect to the given target page
	 *
	 * @param EntityChange $change A change reported from the wikibase repository
	 * @param Title $target The title of a page affected by the change
	 * @param array|null $preparedAttribs Attributes pre-calculated by calling prepareChangeAttributes()
	 *      to avoid re-calculating common change attributes for each target page.
	 *
	 * @return RecentChange
	 */
	public function newRecentChange( EntityChange $change, Title $target, array $preparedAttribs = null ) {
		if ( $preparedAttribs === null ) {
			$preparedAttribs = $this->prepareChangeAttributes( $change );
		}

		$targetSpecificAttributes = $this->buildTargetSpecificAttributes( $change, $target );
		$attribs = array_merge( $preparedAttribs, $targetSpecificAttributes );
		$rc = RecentChange::newFromRow( (object)$attribs );
		$rc->setExtra( array( 'pageStatus' => 'changed' ) );

		return $rc;
	}

	/**
	 * Prepare change attributes for the given EntityChange. This can be used to avoid
	 * re-calculating these attributes for each target page, when processing a change
	 * with respect to a batch of affected target pages.
	 *
	 * @param EntityChange $change
	 *
	 * @return array Associative array of prepared change attributes, for use with the
	 *      $preparedAttribs of newRecentChange().
	 */
	public function prepareChangeAttributes( EntityChange $change ) {
		$rcinfo = $change->getMetadata();

		$fields = $change->getFields();
		$fields['entity_type'] = $change->getEntityId()->getEntityType();

		unset( $fields['info'] );
		$metadata = array_merge( $fields, $rcinfo );

		$isBot = false;
		if ( array_key_exists( 'bot', $metadata ) ) {
			$isBot = $metadata['bot'];
		}

		// compatibility
		if ( array_key_exists( 'user_text', $metadata ) ) {
			$userText = $metadata['user_text'];
		} elseif ( array_key_exists( 'rc_user_text', $metadata ) ) {
			$userText = $metadata['rc_user_text'];
		} else {
			$userText = '';
		}

		$time = isset( $metadata['time'] ) ? $metadata['time'] : wfTimestamp( TS_MW );

		$params = array(
			'wikibase-repo-change' => $metadata,
		);

		return array(
			'rc_user' => 0,
			'rc_user_text' => $userText,
			'rc_comment' => $this->getEditCommentMulti( $change ),
			'rc_type' => RC_EXTERNAL,
			'rc_minor' => true, // for now, always consider these minor
			'rc_bot' => $isBot,
			'rc_patrolled' => true,
			'rc_params' => serialize( $params ),
			'rc_timestamp' => $time,
			'rc_log_type' => null,
			'rc_log_action' => '',
			'rc_source' => self::SRC_WIKIBASE,
			'rc_deleted' => false,
			'rc_new' => false,
		);
	}

	/**
	 * Builds change attribute specific to the given target page.
	 *
	 * @param EntityChange $change
	 * @param Title $target
	 *
	 * @return array
	 */
	private function buildTargetSpecificAttributes( EntityChange $change, Title $target ) {
		$attribs = array(
			'rc_namespace' => $target->getNamespace(),
			'rc_title' => $target->getDBkey(),
			'rc_old_len' => $target->getLength(),
			'rc_new_len' => $target->getLength(),
			'rc_this_oldid' => $target->getLatestRevID(),
			'rc_last_oldid' => $target->getLatestRevID(),
			'rc_cur_id' => $target->getArticleID(),
		);

		$comment = $this->buildTargetSpecificComment( $change, $target );
		if ( $comment !== null ) {
			$attribs['rc_comment'] = $comment;
		}

		return $attribs;
	}

	/**
	 * Get a title specific rc_comment, in case that is needed. Null otherwise.
	 *
	 * @param EntityChange $change
	 * @param Title $target
	 *
	 * @return string|null
	 */
	private function buildTargetSpecificComment( EntityChange $change, Title $target ) {
		if ( !( $change instanceof ItemChange ) ) {
			// Not an ItemChange
			return null;
		}

		$siteLinkDiff = $change->getSiteLinkDiff();
		if ( !$this->siteLinkCommentCreator->needsTargetSpecificSummary( $siteLinkDiff, $target ) ) {
			return null;
		}

		return $this->getEditCommentMulti( $change, $target );
	}

	/**
	 * Returns a human readable comment representing the given changes.
	 *
	 * @param EntityChange $change
	 * @param Title|null $target The page we create an edit summary for. Needed to create an article
	 *         specific edit summary on site link changes. Ignored otherwise.
	 *
	 * @throws MWException
	 * @return string
	 */
	private function getEditCommentMulti( EntityChange $change, Title $target = null ) {
		$info = $change->getInfo();

		if ( isset( $info['changes'] ) ) {
			if ( $info['changes'] === [] ) {
				return '';
			}

			$changes = $info['changes'];
		} else {
			$changes = [ $change ];
		}

		$comments = [];

		foreach ( $changes as $change ) {
			$comments[] = $this->getEditComment( $change, $target );
		}

		if ( count( $comments ) === 1 ) {
			return reset( $comments );
		}

		//@todo: handle overly long lists nicely!
		return $this->language->semicolonList( $comments );
	}

	/**
	 * Returns a human readable comment representing the change.
	 *
	 * @param EntityChange $change the change to get a comment for
	 * @param Title|null $target The page we create an edit summary for. Needed to create an article
	 *         specific edit summary on site link changes. Ignored otherwise.
	 *
	 * @throws MWException
	 * @return string
	 */
	private function getEditComment( EntityChange $change, Title $target = null ) {
		$siteLinkDiff = $change instanceof ItemChange
			? $change->getSiteLinkDiff()
			: null;

		$editComment = '';

		if ( $siteLinkDiff !== null && !$siteLinkDiff->isEmpty() ) {
			$action = $change->getAction();
			$siteLinkComment = $this->siteLinkCommentCreator->getEditComment( $siteLinkDiff, $action, $target );
			$editComment = $siteLinkComment === null ? '' : $siteLinkComment;
		}

		if ( $editComment === '' ) {
			$editComment = $change->getComment();
		}

		if ( $editComment === '' ) {
			// If there is no comment, use something generic. This shouldn't happen.
			wfWarn( 'Failed to find edit comment for EntityChange' );
			$editComment = $this->msg( 'wikibase-comment-update' )->text();
		}

		Assert::postcondition( is_string( $editComment ), '$editComment must be a string' );
		return $editComment;
	}

	/**
	 * @param string $key
	 *
	 * @return Message
	 * @throws MWException
	 */
	private function msg( $key ) {
		$params = func_get_args();
		array_shift( $params );
		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}

		return wfMessage( $key, $params )->inLanguage( $this->language );
	}

}
