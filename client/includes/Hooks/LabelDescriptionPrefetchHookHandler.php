<?php

namespace Wikibase\Client\Hooks;

use ChangesList;
use LogicException;
use MediaWiki\Hook\ChangesListInitRowsHook;
use MediaWiki\Revision\RevisionRecord;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\TermIndexEntry;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Hook handlers for triggering prefetching of labels.
 *
 * Wikibase Client uses the LinkerMakeExternalLink hook handler to display localised Wikibase labels
 * instead of entity Ids in link text.
 * Logic similar  @SummaryParsingPrefetchHelper in repo. Duplicated since some repo logic is redundant for client side lookup
 *
 * @see LinkerMakeExternalLinkHookHandler
 *
 * @license GPL-2.0-or-later
 * @author Joely Rooke WMDE
 */
class LabelDescriptionPrefetchHookHandler implements ChangesListInitRowsHook {
	private TermBuffer $termBuffer;
	private array $termTypes;
	private LanguageFallbackChainFactory $languageFallbackChainFactory;
	private BasicEntityIdParser $entityIdParser;
	private SettingsArray $settings;
	/**
	 * Matching links to properties in edit summaries, such as "[[Q23]]", "[[Property:P123]]"
	 * or "[[wdbeta:Special:EntityPage/P123]]".
	 *
	 */
	public const ENTITY_ID_SUMMARY_REGEXP = '/\[\[[^\[|\]]*(\b[PQ][1-9]\d{0,9})]]/';

	/**
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param SettingsArray $settings
	 * @param TermBuffer $termBuffer
	 */
	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		SettingsArray $settings,
		TermBuffer $termBuffer,
	) {
		$this->termBuffer = $termBuffer;
		$this->entityIdParser = new BasicEntityIdParser();
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->termTypes = [ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_DESCRIPTION ];
		$this->settings = $settings;
	}

	/**
	 * @param ChangesList $changesList
	 * @param IResultWrapper|\stdClass[] $rows
	 */
	public function onChangesListInitRows( $changesList, $rows ): void {
		// Flag for rollout of T388685. Wikibase labels should not be prefetched
		// if are not assigned to links in @LinkerMakeExternalLinkHookHandler
		if ( !$this->settings->getSetting( 'resolveWikibaseLabels' ) ) {
			return;
		}
		$mentionedEntityIds = $this->extractSummaryMentions( $rows );
		if ( !$mentionedEntityIds ) {
			return;
		}
		$languageCodes = $this->languageFallbackChainFactory->newFromContext( $changesList )
			->getFetchLanguageCodes();

		try {
			$this->termBuffer->prefetchTerms(
				$mentionedEntityIds,
				$this->termTypes,
				$languageCodes
			);
		} catch ( StorageException $ex ) {
			wfLogWarning( __METHOD__ . ': ' . $ex->getMessage() );
		}
	}

	/**
	 * @param IResultWrapper|\stdClass[]|RevisionRecord[] $result
	 * @return EntityId[]
	 */
	private function extractSummaryMentions( $result ): array {
		$entityIds = [];
		foreach ( $result as $revisionRow ) {
			$comment = $this->getCommentText( $revisionRow );
			if ( $comment === null ) {
				continue;
			}

			$matches = [];
			if ( !preg_match_all( self::ENTITY_ID_SUMMARY_REGEXP, $comment, $matches, PREG_PATTERN_ORDER ) ) {
				continue;
			}
			foreach ( $matches[1] as $match ) {
				try {
					$entityIds[] = $this->entityIdParser->parse( $match );
				} catch ( EntityIdParsingException $ex ) {
				}
			}
		}

		return $entityIds;
	}

	/**
	 * @param \stdClass|RevisionRecord|null $revisionRow
	 * @return string|null
	 */
	private function getCommentText( $revisionRow ): ?string {
		if ( $revisionRow === null ) {
			return null;
		}

		if ( $revisionRow instanceof RevisionRecord ) {
			$comment = $revisionRow->getComment();
			return $comment === null ? null : $comment->text;
		}

		if ( property_exists( $revisionRow, 'rc_comment_text' ) ) {
			return $revisionRow->rc_comment_text;
		}

		throw new LogicException( 'Rows should have either comment or rc_comment_text field' );
	}
}
