<?php

namespace Wikibase;

use MWException;
use Linker;
use RecentChange;
use InvalidArgumentException;
use UnexpectedValueException;
use \Wikibase\Client\WikibaseClient;

/**
 * Unserializes external changes
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalChangeUnserializer {

	protected $repoLinker;

	/**
	 * @since 0.4
	 *
	 * @param RepoLinker $repoLinker
	 */
	public function __construct( RepoLinker $repoLinker ) {
		$this->repoLinker = $repoLinker;
	}

	/**
	 * @since 0.4
	 *
	 * @param RecentChange $recentChange
	 *
	 * @return ExternalChange
	 */
	public function unserialize( RecentChange $recentChange ) {
		$params = unserialize( $recentChange->getAttribute( 'rc_params' ) );

		if ( !$params || !is_array( $params ) ) {
			throw new MWException( 'Failed to unserialize rc_params in recent change.' );
		}

		if ( ! array_key_exists( 'wikibase-repo-change', $params ) ) {
			throw new UnexpectedValueException( 'Not a Wikibase change' );
		}

		$changeData = $params['wikibase-repo-change'];

		$this->validateChangeData( $changeData );

		$prefixedId = $changeData['object_id'];
		$entityId = $this->extractEntityId( $prefixedId );
		$namespace = $this->repoLinker->getNamespace( $entityId->getEntityType() );

		$externalPage = new ExternalPage(
			strtoupper( $prefixedId ),
			$changeData['page_id'],
			$namespace
		);

		$changeType = $this->getChangeType( $changeData );

		$userName = $recentChange->getAttribute( 'rc_user_text' );
		$title = $recentChange->getTitle();

		$revId = $changeData['rev_id'];
		$parentId = $changeData['parent_id'];
		$timestamp = $recentChange->getAttribute( 'rc_timestamp' );

		$comment = $this->getComment( $changeData );

		return new ExternalChange(
			$externalPage,
			$changeType,
			$userName,
			$title,
			$revId,
			$parentId,
			$timestamp,
			$comment
		);
	}

	/**
	 * @since 0.4
	 *
	 * @param array $changeData
	 *
	 * @throws InvalidArgumentException
	 *
	 * return boolean
	 */
	protected function validateChangeData( $changeData ) {
		if ( ! is_array( $changeData ) ) {
			throw new InvalidArgumentException( 'Invalid Wikibase change' );
		}

		// @todo page_id, rev_id and parent_id might only be needed for add / update types
		$keys = array( 'type', 'page_id', 'rev_id', 'parent_id', 'object_id' );

		foreach( $keys as $key ) {
			if ( !in_array( $key, $changeData ) ) {
				throw new InvalidArgumentException( "$key key missing in change data" );
			}
		}

		return true;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $changeData
	 *
	 * @return string
	 */
	protected function getChangeType( array $changeData ) {
		$validTypes = array( 'remove', 'restore', 'add', 'update' );

		$parts = explode( '~', $changeData['type'] );
		$changeType = $parts[1];

		if ( !in_array( $changeType, $validTypes ) ) {
			throw new InvalidArgumentException( 'invalid change type' );
		}

		return $changeType;
	}

	/**
	 * @since 0.4
	 *
	 * @param string $prefixedId
	 *
	 * @return EntityId
	 */
	protected function extractEntityId( $prefixedId ) {
		$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();
		return $idParser->parse( $prefixedId );
	}

	/**
	 * @since 0.4
	 *
	 * @param array $changeData
	 *
	 * @return string
	 */
	protected function parseComment( array $changeData ) {
		$comment = $changeData['comment'];
		$message = null;

		if ( is_array( $comment ) ) {
			if ( $changeData['type'] === 'wikibase-item~add' ) {
				// @todo: provide a link to the entity
				$message = wfMessage( 'wikibase-comment-linked' )->text();
			} elseif ( array_key_exists( 'sitelink', $comment ) ) {
				// @fixme site link change message
				$message = wfMessage( 'wikibase-comment-update' )->text();
			} else {
				$message = wfMessage( $comment['message'] )->text();
			}
		} else {
			$msg = wfMessage( $comment );
			$message = $msg->exists() ? $msg->text() : $comment;
		}

		return $message;
	}

	/**
	 * @since 0.2
	 *
	 * @param array $changeData
	 *
	 * @return string
	 */
	public function getComment( $changeData ) {
		//TODO: If $changeData['changes'] is set, this is a coalesced change.
		//	  Combine all the comments! Up to some max length?
		if ( array_key_exists( 'composite-comment', $changeData ) ) {
			$commentText = wfMessage( 'wikibase-comment-multi' )->numParams(
				count( $changeData['composite-comment'] ) )->text();
		} elseif ( array_key_exists( 'comment', $changeData  ) ) {
			$commentText = $this->parseComment( $changeData );
		} else {
			$commentText = '';
		}

		return Linker::commentBlock( $commentText );
	}

}
