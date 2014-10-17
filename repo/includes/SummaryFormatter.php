<?php

namespace Wikibase;

use DataValues\DataValue;
use Exception;
use InvalidArgumentException;
use Language;
use MWException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * Formatter for Summary objects
 *
 * @since 0.5
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
	 * @param EntityIdFormatter $idFormatter
	 * @param ValueFormatter $valueFormatter
	 * @param SnakFormatter $snakFormatter
	 * @param Language $language
	 * @param EntityIdParser $idParser
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityIdFormatter $idFormatter, ValueFormatter $valueFormatter,
		SnakFormatter $snakFormatter, Language $language, EntityIdParser $idParser
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
	 * @since 0.5
	 *
	 * @param Summary $summary
	 *
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
	 * Formats the auto summary part of a full summary.
	 *
	 * @since 0.4
	 *
	 * @param Summary $summary
	 *
	 * @throws MWException
	 *
	 * @return string The auto summary arguments comma-separated
	 */
	public function formatAutoSummary( Summary $summary ) {
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

		$strings = array();

		foreach ( $args as $key => $arg ) {
			$strings[$key] = $this->formatArg( $arg );
		}

		return $strings;
	}

	/**
	 * Format an auto summary argument
	 *
	 * @since 0.4
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
				return $this->idFormatter->format( $arg );
			} elseif ( $arg instanceof DataValue ) {
				return $this->valueFormatter->format( $arg );
			} elseif ( method_exists( $arg, '__toString' ) ) {
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
	 * Keys will be left as-is, values will be rendered using formatArg().
	 *
	 * @param array $pairs
	 * @return string[]
	 */
	protected function formatKeyValuePairs( array $pairs ) {
		$list = array();

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
			return $this->idFormatter->format( $this->idParser->parse( $value ) );
		}
		catch ( EntityIdParsingException $ex ) {
			return $value;
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
		$normalizer = WikibaseRepo::getDefaultInstance()->getStringNormalizer();

		$comment = $normalizer->trimToNFC( $comment );
		$summary = $normalizer->trimToNFC( $summary );
		$mergedString = '';
		if ( $comment !== '' ) {
			$mergedString .=  '/* ' . $comment . ' */';
		}
		if ( $summary !== '' ) {
			if ( $mergedString !== '' ) {
				// Having a space after the comment is commonly known from section edits
				$mergedString .= ' ';
			}
			$mergedString .= $this->language->truncate( $summary, $length - strlen( $mergedString ) );
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
	 * @param int $length Max length of the summary
	 * @param int $format Bit field indicating what to include, see the Summary::USE_XXX constants.
	 *
	 * @return string to be used for the summary
	 *
	 * @see Summary::USE_ALL
	 */
	public function formatSummary(
		Summary $summary,
		$length = SUMMARY_MAX_LENGTH,
		$format = Summary::USE_ALL
	) {
		$userSummary = $summary->getUserSummary();

		if ( !is_null( $userSummary ) ) {
			$autoSummary = $userSummary;
		} else {
			$autoSummary = self::formatAutoSummary( $summary );
		}

		$autoComment = $this->formatAutoComment( $summary );

		$autoComment = ( $format & Summary::USE_COMMENT )
			? $this->stringNormalizer->trimToNFC( $autoComment ) : '';
		$autoSummary = ( $format & Summary::USE_SUMMARY )
			? $this->stringNormalizer->trimToNFC( $autoSummary ) : '';

		$totalSummary = self::assembleSummaryString( $autoComment, $autoSummary, $length );
		return $totalSummary;
	}

}
