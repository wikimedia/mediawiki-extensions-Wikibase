<?php

namespace Wikibase\Lib;

use DataValues\DataValue;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatterBase;

/**
 * A ValueFormatter for UnDeserializableValue objects. It acts as a fallback when neither the
 * property type nor the value type are known. It does not show any information from the value, but
 * the message "The value is invalid and cannot be displayed" instead. The message can be changed
 * via an option.
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
	 * @see ValueFormatter::format
	 *
	 * @param DataValue $dataValue Unused in this implementation.
	 *
	 * @return string Unescaped message text.
	 */
	public function format( $dataValue ) {
		$langCode = $this->options->getOption( self::OPT_LANG );

		/** @var Message $msg */
		$msg = $this->options->getOption( self::MESSAGE );
		$msg = $msg->inLanguage( $langCode );

		return $msg->text();
	}

}
