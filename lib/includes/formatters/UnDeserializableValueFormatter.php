<?php

namespace Wikibase\Lib;

use DataValues\DataValue;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatterBase;

/**
 * Formatter for UnDeserializableValue
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class UnDeserializableValueFormatter extends ValueFormatterBase {

	const MESSAGE = 'message';

	/**
	 * @param FormatterOptions|null $options
	 */
	public function __construct( FormatterOptions $options = null ) {
		parent::__construct( $options );

		$this->defaultOption( self::MESSAGE, new Message( 'wikibase-undeserializable-value' ) );
	}

	/**
	 * Formats an UnDeserializableValue
	 *
	 * @since 0.5
	 *
	 * @param DataValue $dataValue Unused in this implementation.
	 *
	 * @return string
	 */
	public function format( $dataValue ) {
		$langCode = $this->options->getOption( self::OPT_LANG );

		/** @var Message $msg */
		$msg = $this->options->getOption( self::MESSAGE );
		$msg = $msg->inLanguage( $langCode );

		return $msg->text();
	}

}
