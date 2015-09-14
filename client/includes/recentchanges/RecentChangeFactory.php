<?php

namespace Wikibase\Client\RecentChanges;

use Language;
use Message;
use MWException;
use RecentChange;
use Title;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\SiteLinkCommentCreator;
use Wikimedia\Assert\Assert;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class RecentChangeFactory {

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
	 * @since 0.5
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

		if ( isset( $fields['info']['changes'] ) ) {
			$changesForComment = $fields['info']['changes'];
		} else {
			$changesForComment = array( $change );
		}

		//TODO: The same change may be reported to several target pages;
		//      The comment we generate should be adapted to the role that page
		//      plays in the change, e.g. when a sitelink changes from one page to another,
		//      the link was effectively removed from one and added to the other page.
		//      This should be handled in buildTargetSpecificAttributes().
		$comment = $this->getEditCommentMulti( $changesForComment );

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
			'rc_type' => RC_EXTERNAL,
			'rc_minor' => true, // for now, always consider these minor
			'rc_bot' => $isBot,
			'rc_patrolled' => true,
			'rc_params' => serialize( $params ),
			'rc_comment' => $comment,
			'rc_timestamp' => $time,
			'rc_log_action' => '',
			'rc_source' => 'wb',
			'rc_deleted' => false,
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

		//TODO: override for "special" changes (e.g. link/unlink, by edit or create/restore/delete)

		return $attribs;
	}

	/**
	 * Returns a human readable comment representing the given changes.
	 *
	 * @param EntityChange[] $changes
	 *
	 * @throws MWException
	 * @return string
	 */
	private function getEditCommentMulti( array $changes ) {
		$comments = array();
		$c = 0;

		foreach ( $changes as $change ) {
			$c++;
			$comments[] = $this->getEditComment( $change );
		}

		if ( $c === 0 ) {
			return '';
		} elseif ( $c === 1 ) {
			return reset( $comments );
		} else {
			//@todo: handle overly long lists nicely!
			return $this->language->semicolonList( $comments );
		}
	}

	/**
	 * Returns a human readable comment representing the change.
	 *
	 * @since 0.4
	 *
	 * @param EntityChange $change the change to get a comment for
	 *
	 * @throws MWException
	 * @return string
	 */
	private function getEditComment( EntityChange $change ) {
		$siteLinkDiff = $change instanceof ItemChange
			? $change->getSiteLinkDiff()
			: null;

		$editComment = '';

		if ( $siteLinkDiff !== null && !$siteLinkDiff->isEmpty() ) {
			$action = $change->getAction();
			$siteLinkComment = $this->siteLinkCommentCreator->getEditComment( $siteLinkDiff, $action );
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
