/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv ) {
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
	vv.experts.StringValue = vv.expert( 'StringValue', PARENT, {
		/**
		 * The nodes of the input element. The input element will be used to display the value
		 * during edit mode as well as during non-edit mode.
		 * @type jQuery
		 */
		$input: null,

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
			.on( 'eachchange', function() {
				notifier.notify( 'change' );
			} );

			var textValue = this.viewState().getTextValue();
			this.$input.val( textValue );
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			if( !this.$input ) {
				return; // destroyed already
			}

			var inputExtender = this.$input.data( 'inputextender' );
			if( inputExtender ) {
				inputExtender.destroy();
			}

			this.$input.off( 'eachchange' );

			this.$input = null;

			PARENT.prototype.destroy.call( this );  // empties viewport
		},

		/**
		 * @see jQuery.valueview.Expert.rawValue
		 */
		rawValue: function() {
			return this.$input.val();
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			// Resize textarea to fit the value (which might be empty):
			this._resizeInput();

			// disable/enable input box
			this.$input.prop('disabled', this.viewState().isDisabled() );
		},

		/**
		 * Will resize the input box to fit its current content.
		 * @since 0.1
		 */
		_resizeInput: function() {
			this.$input.inputautoexpand( {
				expandWidth: false, // TODO: make this optional on valueview level
				expandHeight:true,
				suppressNewLine: true // TODO: make this optional/leave it to parser options
			} );
		},

		/**
		 * @see jQuery.valueview.Expert.focus
		 */
		focus: function() {
			// Move text cursor to the end of the textarea:
			this.$input.focusAt( 'end' );
		},

		/**
		 * @see jQuery.valueview.Expert.blur
		 */
		blur: function() {
			this.$input.blur();
		}
	} );

}( jQuery, jQuery.valueview ) );
