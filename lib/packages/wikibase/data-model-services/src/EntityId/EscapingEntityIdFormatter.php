<?php

namespace Wikibase\DataModel\Services\EntityId;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * EscapingEntityIdFormatter wraps another EntityIdFormatter and
 * applies a transformation (escaping) to that formatter's output.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EscapingEntityIdFormatter implements EntityIdFormatter {

	/**
	 * @var EntityIdFormatter
	 */
	private $formatter;

	/**
	 * @var callable
	 */
	private $escapeCallback;

	/**
	 * @param EntityIdFormatter $formatter A formatter returning plain text.
	 * @param callable $escapeCallback A callable taking plain text and returning escaped text.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityIdFormatter $formatter, $escapeCallback ) {
		if ( !is_callable( $escapeCallback ) ) {
			throw new InvalidArgumentException( '$escapeCallback must be callable' );
		}

		$this->formatter = $formatter;
		$this->escapeCallback = $escapeCallback;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $value
	 *
	 * @return string Typically wikitext or HTML, depending on the $escapeCallback provided.
	 */
	public function formatEntityId( EntityId $value ) {
		$text = $this->formatter->formatEntityId( $value );
		$escaped = call_user_func( $this->escapeCallback, $text );
		return $escaped;
	}

}
