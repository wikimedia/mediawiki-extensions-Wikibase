<?php

namespace Wikibase\Lib\Formatters;

use DataValues\DataValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * A ValueFormatter for UnDeserializableValue objects. It acts as a fallback when neither the
 * property type nor the value type are known. It does not show any information from the value, but
 * the message "The value is invalid and cannot be displayed" instead. The message can be changed
 * via an option.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class UnDeserializableValueFormatter implements ValueFormatter {

	private const OPT_MESSAGE_KEY = 'unDeserializableMessage';

	/**
	 * @var FormatterOptions
	 */
	private $options;

	/**
	 * @param FormatterOptions|null $options
	 */
	public function __construct( FormatterOptions $options = null ) {
		$this->options = $options ?: new FormatterOptions();

		$this->options->defaultOption( self::OPT_MESSAGE_KEY, 'wikibase-undeserializable-value' );
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param DataValue $dataValue Unused in this implementation.
	 *
	 * @return string Unescaped message text.
	 */
	public function format( $dataValue ) {
		$languageCode = $this->options->getOption( ValueFormatter::OPT_LANG );
		$messageKey = $this->options->getOption( self::OPT_MESSAGE_KEY );

		return wfMessage( $messageKey )->inLanguage( $languageCode )->text();
	}

}
