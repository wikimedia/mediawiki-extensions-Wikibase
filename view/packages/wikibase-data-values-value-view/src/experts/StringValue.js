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
	vv.experts.StringValue = vv.expert( 'StringValue', PARENT, function() {
		PARENT.apply( this, arguments );
		this.$input = $( '<textarea/>' );
	}, {
		/**
		 * The nodes of the input element. The input element will be used to display the value
		 * during edit mode.
		 * @type jQuery
		 */
		$input: null,

		/**
		 * @see jQuery.valueview.Expert.init
		 */
		init: function() {
			var notifier = this._viewNotifier;

			this.$input
			.addClass( this.uiBaseClass + '-input valueview-input' )
			.val( this.viewState().getTextValue() )
			.on( 'keydown', function( event ) {
				// Prevent Enter key from adding a new line character:
				if( event.keyCode === $.ui.keyCode.ENTER ) {
					event.preventDefault();
				}
			} )
			.on( 'eachchange', function() {
				notifier.notify( 'change' );
			} )
			.appendTo( this.$viewPort );

			PARENT.prototype.init.call( this );
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			if( this.$input ) {
				this.$input.off( 'eachchange' );
				this.$input = null;
			}

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

			PARENT.prototype.draw.call( this );
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
