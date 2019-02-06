<?php

namespace Wikibase;

use Exception;
use Language;
use Wikibase\Lib\FormatableSummary;

/**
 * A {@link SummaryFormatter} that assumes all arguments are strings.
 *
 * @license GPL-2.0-or-later
 */
class StringSummaryFormatter implements SummaryFormatter {

	/**
	 * @var Language used to format comma-separated lists
	 */
	protected $language;

	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/**
	 * Format the autocomment part of a full summary. Note that the first argument is always the
	 * number of summary arguments supplied via addAutoSummaryArgs() (or the constructor),
	 * and the second one is always the language code supplied via setLanguage()
	 * (or the constructor).
	 *
	 * @param FormatableSummary $summary
	 *
	 * @return string with a formatted comment, or possibly an empty string
	 */
	public function formatAutoComment( FormatableSummary $summary ) {
		$composite = $summary->getMessageKey();
		$summaryArgCount = count( $summary->getAutoSummaryArgs() );

		$commentArgs = array_merge(
			[ $summaryArgCount, $summary->getLanguageCode() ],
			$summary->getCommentArgs()
		);

		//XXX: we might want to use different formatters for autocomment and summary.
		$parts = $this->formatArgList( $commentArgs );
		$joinedParts = implode( '|', $parts );

		if ( $joinedParts !== '' ) {
			$composite .= ':' . $joinedParts;
		}

		return $composite;
	}

	/**
	 * Formats the auto summary part of a full summary.
	 *
	 * @param FormatableSummary $summary
	 *
	 * @return string The auto summary arguments comma-separated
	 */
	public function formatAutoSummary( FormatableSummary $summary ) {
		$summaryArgs = $summary->getAutoSummaryArgs();
		$parts = $this->formatArgList( $summaryArgs );

		$count = count( $parts );

		if ( $count === 0 ) {
			return '';
		} else {
			$parts = array_filter(
				$parts,
				function ( $arg ) {
					return $arg !== '';
				}
			);

			return $this->language->commaList( $parts );
		}
	}

	/**
	 * @param array $args
	 *
	 * @return string[]
	 */
	protected function formatArgList( array $args ) {
		if ( !empty( $args ) && !isset( $args[0] ) ) {
			// turn assoc array into a list
			$args = $this->formatKeyValuePairs( $args );
		}

		$strings = [];

		foreach ( $args as $key => $arg ) {
			$strings[$key] = $this->formatArg( $arg );
		}

		return $strings;
	}

	/**
	 * Format an auto summary argument
	 *
	 * @param mixed $arg
	 *
	 * @return string
	 */
	protected function formatArg( $arg ) {
		try {
			if ( method_exists( $arg, '__toString' ) ) {
				return strval( $arg );
			} elseif ( is_object( $arg ) ) {
				return '<' . get_class( $arg ) . '>';
			} elseif ( is_array( $arg ) ) {
				if ( !empty( $arg ) && !isset( $arg[0] ) ) {
					// turn assoc array into a list
					$arg = $this->formatKeyValuePairs( $arg );
				}

				$strings = $this->formatArgList( $arg );
				return $this->language->commaList( $strings );
			} else {
				return strval( $arg );
			}
		} catch ( Exception $ex ) {
			wfWarn( __METHOD__ . ': failed to render value: ' . $ex->getMessage() );
		}

		return '?';
	}

	/**
	 * Turns an associative array into a list of strings by rendering each key/value pair.
	 * Keys and values will be rendered using formatKey() and formatValue(),
	 * which by default means keys will be left as-is and values will use formatArg().
	 *
	 * @param array $pairs
	 * @return string[]
	 */
	protected function formatKeyValuePairs( array $pairs ) {
		$list = [];

		foreach ( $pairs as $key => $value ) {
			$key = $this->formatKey( $key );
			$value = $this->formatArg( $value );
			$list[] = "$key: $value";
		}

		return $list;
	}

	/**
	 * @param string|int $key
	 * @return string
	 */
	protected function formatKey( $key ) {
		return strval( $key );
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	protected function formatValue( $value ) {
		return $this->formatArg( $value );
	}

	/**
	 * Merge the total summary
	 *
	 * @param string $autoComment autocomment part, will be placed in a block comment
	 * @param string $autoSummary human readable string to be appended after the autocomment part
	 * @param string $userSummary user provided summary to be appended after the autoSummary
	 *
	 * @return string to be used for the summary
	 */
	protected function assembleSummaryString( $autoComment, $autoSummary, $userSummary ) {
		$mergedString = '';
		$autoComment = trim( $autoComment );
		$autoSummary = trim( $autoSummary );
		$userSummary = trim( $userSummary );

		if ( $autoComment !== '' ) {
			$mergedString .= '/* ' . $autoComment . ' */ ';
		}

		if ( $autoSummary !== '' && $userSummary !== '' ) {
			$mergedString .= $this->language->commaList( [ $autoSummary, $userSummary ] );
		} elseif ( $autoSummary !== '' ) {
			$mergedString .= $autoSummary;
		} elseif ( $userSummary !== '' ) {
			$mergedString .= $userSummary;
		}

		// note: truncation to proper comment length limit done by CommentStore
		return rtrim( $mergedString );
	}

	/**
	 * Format the given summary
	 *
	 * @param FormatableSummary $summary
	 *
	 * @return string to be used for the summary
	 */
	public function formatSummary( FormatableSummary $summary ) {
		$userSummary = $summary->getUserSummary();

		return $this->assembleSummaryString(
			$this->formatAutoComment( $summary ),
			$this->formatAutoSummary( $summary ),
			$userSummary === null ? '' : $userSummary
		);
	}

}
