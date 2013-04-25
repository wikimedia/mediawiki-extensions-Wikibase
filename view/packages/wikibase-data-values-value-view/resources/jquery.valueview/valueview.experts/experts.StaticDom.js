/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv ) {
	'use strict';

	var PARENT = vv.Expert;

	/**
	 * Valueview expert which will display its current value based on an injected callback which
	 * is responsible for returning the DOM to be drawed. The DOM should be static since this
	 * expert has no further logic required for handling interactive values.
	 *
	 * NOTE: This expert is useful when used as the static part of a BifidExpert. It can be used to
	 *  display the value in some specialized form, e.g. as a link or formatted text or both mixed.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 *
	 * @option domBuilder {Function} A callback function called whenever the DOM for displaying the
	 *         current raw value is required. First parameter of the callback is the current raw
	 *         value of the expert, second parameter is the expert's related ViewState object.
	 *
	 * @option baseExpert {Function} Constructor of an expert whose "parser" and "rawValueCompare"
	 *         functions will be borrowed. This is required because this expert doesn't need to
	 *         know what kind of values it handles.
	 *
	 * TODO: the "baseExpert" function is conceptually not that nice. It is required because the
	 *  a static DOM expert doesn't need to know what kind of values it handles.
	 */
	vv.experts.StaticDom = vv.expert( 'staticdom', PARENT, {
		/**
		 * Current value.
		 * @type {*}
		 */
		value: null,

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this._value = null;
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Returns a parser suitable for parsing the raw value returned by rawValue().
		 *
		 * @since 0.1
		 * @abstract
		 *
		 * @return valueParsers.Parser
		 */
		parser: function() {
			return this._options.baseExpert.prototype.parser.call( this );
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		_getRawValue: function() {
			return this._value;
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		_setRawValue: function( rawValue ) {
			// TODO: this should probably also make use of the "baseExpert" since there is no
			//  handling of the value at all here.
			this._value = rawValue;
		},

		/**
		 * @see jQuery.valueview.Expert.rawValueCompare
		 */
		rawValueCompare: function( value1, value2 ) {
			return this._options.baseExpert.prototype.rawValueCompare.apply( this, arguments );
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			// Build DOM as specified by callback:
			var $customDom = this._options.domBuilder( this.rawValue(), this._viewState );
			this.$viewPort.empty().append( $customDom );
		}
	} );

}( jQuery, jQuery.valueview ) );
