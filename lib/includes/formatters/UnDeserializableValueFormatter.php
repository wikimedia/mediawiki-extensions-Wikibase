<?php

namespace Wikibase\Lib;

use DataValues\DataValue;
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

	const OPT_MESSAGE_KEY = 'unDeserializableMessage';

	/**
	 * @param FormatterOptions|null $options
	 */
	public function __construct( FormatterOptions $options = null ) {
		parent::__construct( $options );

		$this->defaultOption( self::OPT_MESSAGE_KEY, 'wikibase-undeserializable-value' );
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
		$languageCode = $this->options->getOption( self::OPT_LANG );
		$messageKey = $this->options->getOption( self::OPT_MESSAGE_KEY );

		return wfMessage( $messageKey )->inLanguage( $languageCode )->text();
	}

}
