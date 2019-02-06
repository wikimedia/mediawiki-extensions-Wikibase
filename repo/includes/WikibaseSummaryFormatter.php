<?php

namespace Wikibase;

use DataValues\DataValue;
use Exception;
use InvalidArgumentException;
use Language;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;

/**
 * A {@link SummaryFormatter} with special support for entity IDs
 * and data values in the auto summary arguments.
 *
 * @license GPL-2.0-or-later
 */
class WikibaseSummaryFormatter extends StringSummaryFormatter {

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

		parent::__construct( $language );

		$this->idFormatter = $idFormatter;
		$this->valueFormatter = $valueFormatter;
		$this->snakFormatter = $snakFormatter;
		$this->idParser = $idParser;

		$this->stringNormalizer = new StringNormalizer();
	}

	protected function formatArg( $arg ) {
		try {
			if ( $arg instanceof Snak ) {
				return $this->snakFormatter->formatSnak( $arg );
			} elseif ( $arg instanceof EntityId ) {
				return $this->idFormatter->formatEntityId( $arg );
			} elseif ( $arg instanceof DataValue ) {
				return $this->valueFormatter->format( $arg );
			} else {
				return parent::formatArg( $arg );
			}
		} catch ( Exception $ex ) {
			wfWarn( __METHOD__ . ': failed to render value: ' . $ex->getMessage() );
		}

		return '?';
	}

	protected function formatKey( $key ) {
		//HACK: if the key *looks* like an entity id, apply entity id formatting.
		return $this->formatIfEntityId( $key );
	}

	private function formatIfEntityId( $value ) {
		try {
			return $this->idFormatter->formatEntityId( $this->idParser->parse( $value ) );
		} catch ( EntityIdParsingException $ex ) {
			return $value;
		}
	}

	protected function assembleSummaryString( $autoComment, $autoSummary, $userSummary ) {
		$autoComment = $this->stringNormalizer->trimToNFC( $autoComment );
		$autoSummary = $this->stringNormalizer->trimToNFC( $autoSummary );
		$userSummary = $this->stringNormalizer->trimToNFC( $userSummary );
		return parent::assembleSummaryString( $autoComment, $autoSummary, $userSummary );
	}

}
