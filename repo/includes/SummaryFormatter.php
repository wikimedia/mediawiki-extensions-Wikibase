<?php

namespace Wikibase\Repo;

use DataValues\DataValue;
use Exception;
use InvalidArgumentException;
use Language;
use MWException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\StringNormalizer;

/**
 * Formatter for Summary objects
 *
 * @license GPL-2.0-or-later
 */
class SummaryFormatter {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var EntityIdFormatter
	 */
	private $idFormatter;

	/**
	 * @var ValueFormatter
	 */
	private $valueFormatter;

	/**
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @param EntityIdFormatter $idFormatter Please note that the magic label substitution we apply
	 *     on top of this only works in case this returns links without display text.
	 * @param ValueFormatter $valueFormatter
	 * @param SnakFormatter $snakFormatter
	 * @param Language $language
	 * @param EntityIdParser $idParser
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdFormatter $idFormatter,
		ValueFormatter $valueFormatter,
		SnakFormatter $snakFormatter,
		Language $language,
		EntityIdParser $idParser
	) {
		if ( $snakFormatter->getFormat() !== SnakFormatter::FORMAT_PLAIN ) {
			throw new InvalidArgumentException(
				'Expected $snakFormatter to procude text/plain output, not '
				. $snakFormatter->getFormat() );
		}

		$this->idFormatter = $idFormatter;
		$this->valueFormatter = $valueFormatter;
		$this->snakFormatter = $snakFormatter;
		$this->language = $language;
		$this->idParser = $idParser;

		$this->stringNormalizer = new StringNormalizer();
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
	 * @throws MWException
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
			if ( $arg instanceof Snak ) {
				return $this->snakFormatter->formatSnak( $arg );
			} elseif ( $arg instanceof EntityId ) {
				return $this->idFormatter->formatEntityId( $arg );
			} elseif ( $arg instanceof DataValue ) {
				return $this->valueFormatter->format( $arg );
			} elseif ( is_object( $arg ) ) {
				if ( method_exists( $arg, '__toString' ) ) {
					return strval( $arg );
				} else {
					return '<' . get_class( $arg ) . '>';
				}
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
	 * Keys will be left as-is, values will be rendered using formatArg().
	 *
	 * @param array $pairs
	 * @return string[]
	 */
	protected function formatKeyValuePairs( array $pairs ) {
		$list = [];

		foreach ( $pairs as $key => $value ) {
			if ( is_string( $key ) ) {
				//HACK: if the key *looks* like an entity id,
				//      apply entity id formatting.
				$key = $this->formatIfEntityId( $key );
			}

			$value = $this->formatArg( $value );
			$list[] = "$key: $value";
		}

		return $list;
	}

	private function formatIfEntityId( $value ) {
		try {
			return $this->idFormatter->formatEntityId( $this->idParser->parse( $value ) );
		} catch ( EntityIdParsingException $ex ) {
			return $value;
		}
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
	private function assembleSummaryString( $autoComment, $autoSummary, $userSummary ): string {
		$mergedString = '';
		$autoComment = $this->stringNormalizer->trimToNFC( $autoComment );
		$autoSummary = $this->stringNormalizer->trimToNFC( $autoSummary );
		$userSummary = $this->stringNormalizer->trimToNFC( $userSummary );

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

		// leftover entities should be removed, but its not clear how this shall be done
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
	public function formatSummary( FormatableSummary $summary ): string {
		$userSummary = $summary->getUserSummary();

		return $this->assembleSummaryString(
			$this->formatAutoComment( $summary ),
			$this->formatAutoSummary( $summary ),
			$userSummary === null ? '' : $userSummary
		);
	}

}
