<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use LogicException;
use MediaWiki\Revision\RevisionRecord;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * A helper class for parsing and prefetching terms of entities mentioned in edit summaries.
 *
 * @license GPL-2.0-or-later
 */
class SummaryParsingPrefetchHelper {

	private TermBuffer $termBuffer;
	private BasicEntityIdParser $entityIdParser;

	public function __construct(
		TermBuffer $termBuffer
	) {
		$this->termBuffer = $termBuffer;
		$this->entityIdParser = new BasicEntityIdParser();
	}

	/**
	 * Matching links to properties in in edit summaries, such as "[[Q23]]", "[[Property:P123]]"
	 * or "[[wdbeta:Special:EntityPage/P123]]".
	 */
	public const ENTITY_ID_SUMMARY_REGEXP = '/\[\[[^\[|\]]*(\b[PQ][1-9]\d{0,9})]]/';

	/**
	 * @param IResultWrapper|\stdClass[]|RevisionRecord[] $rows
	 * @param array $languageCodes
	 * @param array $termTypes
	 */
	public function prefetchTermsForMentionedEntities( $rows, array $languageCodes, array $termTypes ): void {
		$mentionedEntityIds = $this->extractSummaryMentions( $rows );
		if ( !$mentionedEntityIds ) {
			return;
		}

		$this->termBuffer->prefetchTerms(
			$mentionedEntityIds,
			$termTypes,
			$languageCodes
		);
	}

	/**
	 * @param IResultWrapper|\stdClass[]|RevisionRecord[] $result
	 * @return EntityId[]
	 */
	public function extractSummaryMentions( $result ): array {
		$entityIds = [];
		foreach ( $result as $revisionRow ) {
			$comment = $this->getCommentText( $revisionRow );
			if ( $comment === null ) {
				continue;
			}

			if ( !preg_match_all( self::ENTITY_ID_SUMMARY_REGEXP, $comment, $matches, PREG_PATTERN_ORDER ) ) {
				continue;
			}
			foreach ( $matches[1] as $idSerialization ) {
				try {
					$entityIds[] = $this->entityIdParser->parse( $idSerialization );
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
	private function getCommentText( $revisionRow ) {
		if ( $revisionRow === null ) {
			return null;
		}

		if ( $revisionRow instanceof RevisionRecord ) {
			$comment = $revisionRow->getComment();
			return $comment === null ? null : $comment->text;
		}

		if ( property_exists( $revisionRow, 'rc_comment_text' ) ) {
			return $revisionRow->rc_comment_text;
		} elseif ( property_exists( $revisionRow, 'rev_comment_text' ) ) {
			return $revisionRow->rev_comment_text;
		}

		throw new LogicException( 'Rows should have either rc_comment_text or rev_comment_text field' );
	}
}
