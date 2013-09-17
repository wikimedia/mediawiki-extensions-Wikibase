<?php

namespace Wikibase;

use DataValues\DataValue;
use Language;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * Formatter for Summary objects
 *
 * @since 0.5
 *
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 *
 * @author John Erling Blad
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SummaryFormatter {

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var EntityIdFormatter
	 */
	protected $idFormatter;

	/**
	 * @var ValueFormatter
	 */
	protected $valueFormatter;

	/**
	 * @var SnakFormatter
	 */
	protected $snakFormatter;

	/**
	 * @param EntityIdFormatter $idFormatter
	 * @param ValueFormatter $valueFormatter
	 * @param SnakFormatter $snakFormatter
	 */
	public function __construct( EntityIdFormatter $idFormatter, ValueFormatter $valueFormatter, SnakFormatter $snakFormatter, Language $language ) {
		$this->idFormatter = $idFormatter;
		$this->valueFormatter = $valueFormatter;
		$this->snakFormatter = $snakFormatter;
		$this->language = $language;

		$this->stringNormalizer = new StringNormalizer();
	}

	/**
	 * Format the autocomment part of a full summary. Note that the first argument is always the
	 * number of summary arguments supplied via addAutoSummaryArgs() (or the constructor),
	 * and the second one is always the language code supplied via setLanguage()
	 * (or the constructor).
	 *
	 * @since 0.5
	 *
	 * @param Summary $summary
	 * @return string with a formatted comment, or possibly an empty string
	 */
	public function formatAutoComment( Summary $summary ) {
		$messageKey = $summary->getMessageKey();
		$summaryArgCount = count( $summary->getAutoSummaryArgs() );

		$commentArgs = array_merge(
			array( $summaryArgCount, $summary->getLanguageCode() ),
			$summary->getCommentArgs()
		);

		//XXX: we might want to use different formatters for autocomment and summary.
		$parts = $this->formatArgList( $commentArgs );
		$joinedParts = implode( '|', $parts );

		$composite = ( 0 < strlen($joinedParts) )
			? implode( ':', array( $messageKey, $joinedParts ) )
			: $messageKey;

		return $composite;
	}

	/**
	 * Format the autosummary part of a full summary
	 *
	 * @since 0.4
	 *
	 * @param Summary $summary
	 *
	 * @throws \MWException
	 *
	 * @return string The $parts concatenated
	 */
	public function formatAutoSummary( Summary $summary ) {
		$summaryArgs = $summary->getAutoSummaryArgs();
		$parts = $this->formatArgList( $summaryArgs );

		$count = count( $parts );

		if ( $count === 0 ) {
			return '';
		} else {
			// @todo have some sort of key value formatter
			return $this->language->commaList( $parts );
		}
	}

	/**
	 * @param mixed[] $args
	 *
	 * @return string[]
	 */
	protected function formatArgList( array $args ) {
		$strings = array();

		foreach ( $args as $key => $arg ) {
			$strings[$key] = $this->formatArg( $arg );
		}

		return $strings;
	}

	/**
	 * Format an autosummary argument
	 *
	 * @since 0.4
	 *
	 * @param mixed $arg
	 *
	 * @return string
	 */
	protected function formatArg( $arg ) {
		if ( $arg instanceof Snak ) {
			return $this->snakFormatter->formatSnak( $arg );
		} elseif ( $arg instanceof EntityId ) {
			return $this->idFormatter->format( $arg );
		} elseif ( $arg instanceof DataValue ) {
			return $this->valueFormatter->format( $arg );
		} elseif ( method_exists( $arg, '__toString' ) ) {
			return strval( $arg );
		} elseif ( is_object( $arg ) ) {
			return '<' . get_class( $arg ) . '>';
		} elseif ( is_array( $arg ) ) {
			//TODO: preserve keys in assoc array
			$strings = $this->formatArgList( $arg );
			return $this->language->commaList( $strings );
		} else {
			return strval( $arg );
		}
	}

	/**
	 * Merge the total summary
	 *
	 * @since 0.5
	 *
	 * @param string $comment autocomment part, will be placed in a block comment
	 * @param string $summary human readable string to be appended after the autocomment part
	 * @param int $length max length of the string
	 *
	 * @return string to be used for the summary
	 */
	private function assembleSummaryString( $comment, $summary, $length = SUMMARY_MAX_LENGTH ) {
		global $wgContLang;
		$normalizer = WikibaseRepo::getDefaultInstance()->getStringNormalizer();

		$comment = $normalizer->trimToNFC( $comment );
		$summary = $normalizer->trimToNFC( $summary );
		$mergedString = '';
		if ( $comment !== '' ) {
			$mergedString .=  "/* $comment */";
		}
		if ( $summary !== "" ) {
			$mergedString .= $wgContLang->truncate( $summary, $length - strlen( $mergedString ) );
		}

		// leftover entities should be removed, but its not clear how this shall be done
		return $mergedString;
	}

	/**
	 * Format the given summary
	 *
	 * @since 0.5
	 *
	 * @param Summary $summary
	 * @param int $length max length of the summary
	 * @param int $format bitset indicating what to include, see the USE_XXX constants.
	 *
	 * @return string to be used for the summary
	 */
	public function formatSummary( Summary $summary, $length = SUMMARY_MAX_LENGTH, $format = Summary::USE_ALL ) {
		$summaryArgs = $summary->getAutoSummaryArgs();
		$userSummary = $summary->getUserSummary();

		if ( !is_null( $userSummary ) ) {
			$autoSummary = $userSummary;
		} else {
			$autoSummary = self::formatAutoSummary( $summary );
		}

		$autoComment = $this->formatAutoComment( $summary );

		$autoComment = ( $format & Summary::USE_COMMENT ) ? $this->stringNormalizer->trimToNFC( $autoComment ) : '';
		$autoSummary = ( $format & Summary::USE_SUMMARY ) ? $this->stringNormalizer->trimToNFC( $autoSummary ) : '';

		$totalSummary = self::assembleSummaryString( $autoComment, $autoSummary, $length );
		return $totalSummary;
	}

}
