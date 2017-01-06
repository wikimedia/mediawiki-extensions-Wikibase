<?php

namespace Wikibase\Lib;

use DataValues\DataValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatterBase;

/**
 * A ValueFormatter for UnDeserializableValue objects. It acts as a fallback when neither the
 * property type nor the value type are known. It does not show any information from the value, but
 * the message "The value is invalid and cannot be displayed" instead. The message can be changed
 * via an option.
 *
 * @license GPL-2.0+
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
	 * @see ValueFormatter::format
	 *
	 * @param DataValue $dataValue Unused in this implementation.
	 *
	 * @return string Unescaped message text.
	 */
	public function format( $dataValue ) {
		$languageCode = $this->options->getOption( self::OPT_LANG );
		$messageKey = $this->options->getOption( self::OPT_MESSAGE_KEY );

		return wfMessage( $messageKey )->inLanguage( $languageCode )->text();
	}

}
