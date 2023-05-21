<?php

namespace Wikibase\Client\RecentChanges;

use CentralIdLookup;
use ExternalUserNames;
use Language;
use Message;
use MWException;
use RecentChange;
use Title;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class RecentChangeFactory {

	/**
	 * Classification of Wikibase changes in the rc_source column of the
	 * recentchanges table.
	 */
	public const SRC_WIKIBASE = 'wb';

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var SiteLinkCommentCreator
	 */
	private $siteLinkCommentCreator;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @var string
	 */
	private $localDomainId;

	/**
	 * @var CentralIdLookup|null
	 */
	private $centralIdLookup;

	/**
	 * @var ExternalUserNames|null
	 */
	private $externalUsernames;

	/**
	 * @param Language $language Language to format in
	 * @param SiteLinkCommentCreator $siteLinkCommentCreator
	 * @param EntitySourceDefinitions $entitySourceDefinitions
	 * @param ClientDomainDb $clientDomainDb
	 * @param CentralIdLookup|null $centralIdLookup CentralIdLookup, or null if
	 *   this repository is not connected to a central user system (see
	 *   CentralIdLookupFactory::getNonLocalLookup).
	 * @param ExternalUserNames|null $externalUsernames
	 */
	public function __construct(
		Language $language,
		SiteLinkCommentCreator $siteLinkCommentCreator,
		EntitySourceDefinitions $entitySourceDefinitions,
		ClientDomainDb $clientDomainDb,
		CentralIdLookup $centralIdLookup = null,
		ExternalUserNames $externalUsernames = null
	) {
		$this->language = $language;
		$this->siteLinkCommentCreator = $siteLinkCommentCreator;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->localDomainId = $clientDomainDb->domain();  // T294287
		$this->centralIdLookup = $centralIdLookup;
		$this->externalUsernames = $externalUsernames;
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

		// Creating a RecentChange by passing a faked-up row needs the correct
		// fields, which are changing in Ic3a434c0.
		$attribs += [
			'rc_comment_text' => $attribs['rc_comment'],
			'rc_comment_data' => null,
		];

		$rc = RecentChange::newFromRow( (object)$attribs );
		$rc->setExtra( [ 'pageStatus' => 'changed' ] );

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

		unset( $fields[ChangeRow::INFO] );
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

		$time = $metadata['time'] ?? wfTimestamp( TS_MW );

		$params = [
			'wikibase-repo-change' => $metadata,
		];

		$repoUserId = $change->getUserId();
		$clientUserId = $this->getClientUserId( $repoUserId, $metadata, $this->isChangeFromLocalDb( $change ) );

		// If the user could not be found in client but exists in repo
		if ( $this->externalUsernames !== null && $clientUserId === 0 && $repoUserId !== 0 ) {
			$userText = $this->externalUsernames->addPrefix( $userText );
		}

		$attribs = [
			'rc_user' => $clientUserId,
			'rc_user_text' => $userText,
			'rc_comment' => $this->getEditCommentMulti( $change ),
			'rc_type' => RC_EXTERNAL,
			'rc_minor' => true, // for now, always consider these minor
			'rc_bot' => $isBot,
			'rc_patrolled' => RecentChange::PRC_AUTOPATROLLED,
			'rc_params' => serialize( $params ),
			'rc_timestamp' => $time,
			'rc_logid' => 0,
			'rc_log_type' => null,
			'rc_log_action' => '',
			'rc_source' => self::SRC_WIKIBASE,
			'rc_deleted' => false,
			'rc_new' => false,
		];

		// Creating a RecentChange by passing a faked-up row needs the correct
		// fields, which are changing in Ic3a434c0.
		$attribs += [
			'rc_comment_text' => $attribs['rc_comment'],
			'rc_comment_data' => null,
		];

		return $attribs;
	}

	private function isChangeFromLocalDb( EntityChange $change ): bool {
		$entityType = $change->getEntityId()->getEntityType();
		$source = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( $entityType );
		if ( $source === null ) {
			return false;
		}

		$dbName = $source->getDatabaseName();

		if ( $dbName === false || $dbName === $this->localDomainId ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets the client's user ID from the repo user ID and EntityChange's metadata
	 *
	 * @param int $repoUserId Original user ID from the repository
	 * @param array $metadata EntityChange metadata
	 * @param bool $changeComesFromLocalDb
	 *
	 * @return int User ID for the current (client) wiki
	 */
	private function getClientUserId( $repoUserId, array $metadata, bool $changeComesFromLocalDb ) {
		if ( $repoUserId === 0 ) {
			// Logged out on repo just copied to client
			return 0;
		}

		// We decided to put 0 for users that can not be matched.  As a
		// result, only users that are centralized and exist on both wikis
		// will be marked as logged in (and be mapped to the local user).
		//
		// We don't currently auto-create the local account if they've
		// never logged into the client.  This can be done with
		// CentralAuth, but AFAIK there is not a portable way to do this.

		// Temporary compatibility until Ie7b9c482cf6a0dd7215b34841efd86fb51be651a
		// has been deployed long enough that all rows have it.
		// See @ref docs_topics_change-propagation for why it can be 0 other than pre-deploy rows.
		if ( $this->centralIdLookup
			&& isset( $metadata['central_user_id'] )
			&& $metadata['central_user_id'] !== 0
		) {
			$user = $this->centralIdLookup->localUserFromCentralId( $metadata['central_user_id'] );

			if ( $user ) {
				return $user->getId();
			}
		}

		if ( $changeComesFromLocalDb ) {
			return $repoUserId;
		}

		return 0;
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
		$attribs = [
			'rc_namespace' => $target->getNamespace(),
			'rc_title' => $target->getDBkey(),
			'rc_old_len' => $target->getLength(),
			'rc_new_len' => $target->getLength(),
			'rc_this_oldid' => $target->getLatestRevID(),
			'rc_last_oldid' => $target->getLatestRevID(),
			'rc_cur_id' => $target->getArticleID(),
		];

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
	 * @param RecentChange $rc
	 * @return bool
	 */
	public static function isWikibaseChange( RecentChange $rc ) {
		return $rc->getAttribute( 'rc_source' ) === self::SRC_WIKIBASE;
	}

	/**
	 * @param string $key
	 *
	 * @return Message
	 * @throws MWException
	 */
	private function msg( $key, ...$params ) {
		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}

		return wfMessage( $key, $params )->inLanguage( $this->language );
	}

}
