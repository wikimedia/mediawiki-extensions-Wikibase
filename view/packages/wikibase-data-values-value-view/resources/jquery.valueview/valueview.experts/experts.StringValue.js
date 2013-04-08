/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( dv, vp, $, vv ) {
	'use strict';

	var PARENT = vv.Expert;

	/**
	 * Valueview expert for adding string data value support to valueview widget.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.StringValue = vv.expert( 'stringvalue', {
		/**
		 * The nodes of the input element. The input element will be used to display the value
		 * during edit mode as well as during non-edit mode.
		 * @type jQuery
		 */
		$input: null,

		/**
		 * Will not be false if a new value (null for no value) has been set by _setRawValue()
		 * function but draw() has not yet been called for displaying that value.
		 * @type string|null|false
		 */
		_newValue: null,

		/**
		 * @see jQuery.valueview.Expert._init
		 */
		_init: function() {
			var notifier = this._viewNotifier;

			this.$input = $( '<textarea/>', {
				'class': this.uiBaseClass + '-input valueview-input',
				'type': 'text'
			} )
			.appendTo( this.$viewPort )
			.eachchange( function() {
				notifier.notify( 'change' );
			} );
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			PARENT.prototype.destroy.call( this );
			// TODO: destroy input. Should unbind "eachchange" event and remove "inputAutoExpand"
			//  functionality. Also see valueview.Expert's destroy TODO.
		},

		/**
		 * @see Query.valueview.Expert.parser
		 */
		parser: function() {
			return new vp.StringParser();
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 */
		_getRawValue: function() {
			var value = this._newValue !== false ? this._newValue : this.$input.val();

			// TODO: should be left to parser to decide whether an empty string or just a space
			//  is considered an empty value or what it is.
			if( $.trim( value ) === '' ) {
				return null;
			}
			return value;
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 */
		_setRawValue: function( rawValue ) {
			if( typeof rawValue !== 'string' ) {
				rawValue = null;
			}
			this._newValue = rawValue;
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			if( this._newValue !== false ) {
				var textValue = this._newValue === null ? '' : this._newValue;
				this._newValue = false;

				// Display value and resize textarea to fit for the value:
				this.$input.val( textValue ).inputAutoExpand( {
					expandWidth: false, // TODO: make this optional on valueview level
					expandHeight:true,
					suppressNewLine: true // TODO: make this optional/leave it to parser options
				} );
			}

			// We always use the textare for displaying the value, only in edit mode we format the
			// textare as an input field though.
			if( this._viewState.isInEditMode() ) {
				// in EDIT MODE:
				this.$input.prop( {
					readOnly: false,
					spellcheck: true, // TODO: doesn't really work, seems fully disabled in Chrome now
					placeholder: '', // TODO:, how to get options here? E.g. this._viewState.option( 'inputPlaceholder' ),
					disabled: this._viewState.isDisabled() // disable/enable input box
				} ).removeProp( 'tabIndex' );
			} else {
				// in NON-EDIT MODE:
				this.$input.prop( {
					// Using readOnly instead of disabled since IE would overwrite font color with
					// default system style regardless of any applied css rules.
					readOnly: true,
					tabIndex: -1,
					spellcheck: false,
					placeholder: '' // don't want to see any placeholder text in static mode
				} ).removeProp( 'disabled' );
			}
		},

		/**
		 * @see jQuery.valueview.Expert.focus
		 */
		focus: function() {
			if( !this._viewState.isInEditMode() ) {
				return; // no need to execute following code since focus won't be set anyhow
			}
			// Move text cursor to the end of the textarea:
			var value = this.$input.val();
			this.$input.val( '' );
			this.$input.focus();
			this.$input.val( value );
		},

		/**
		 * @see jQuery.valueview.Expert.blur
		 */
		blur: function() {
			this.$input.blur();
		}
	} );

}( dataValues, valueParsers, jQuery, jQuery.valueview ) );
