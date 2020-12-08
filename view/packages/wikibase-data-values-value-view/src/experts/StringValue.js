module.exports = ( function( $, vv ) {
	'use strict';

	var PARENT = vv.Expert;

	/**
	 * `Valueview` expert for adding `String` data value support to `valueview` widget.
	 *
	 * @class jQuery.valueview.experts.StringValue
	 * @extends jQuery.valueview.Expert
	 * @since 0.1
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	vv.experts.StringValue = vv.expert( 'StringValue', PARENT, function() {
		PARENT.apply( this, arguments );
		this.$input = $( '<textarea/>' );
	}, {
		/**
		 * The nodes of the input element. The input element will be used to display the value
		 * during edit mode.
		 *
		 * @property {jQuery}
		 * @protected
		 * @readonly
		 */
		$input: null,

		/**
		 * @inheritdoc
		 */
		init: function() {
			var notifier = this._viewNotifier;

			this.$input
			.addClass( this.uiBaseClass + '-input valueview-input' )
			.val( this.viewState().getTextValue() )
			.on( 'keydown', function( event ) {
				// Prevent Enter key from adding a new line character:
				if ( event.keyCode === $.ui.keyCode.ENTER ) {
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
		 * @inheritdoc
		 */
		destroy: function() {
			if ( this.$input ) {
				this.$input.off( 'eachchange' );
				this.$input = null;
			}

			PARENT.prototype.destroy.call( this ); // empties viewport
		},

		/**
		 * @inheritdoc
		 * @return {string}
		 */
		rawValue: function() {
			return this.$input.val();
		},

		/**
		 * @inheritdoc
		 */
		draw: function() {
			// Resize textarea to fit the value (which might be empty):
			this._resizeInput();

			// disable/enable input box
			this.$input.prop( 'disabled', this.viewState().isDisabled() );

			PARENT.prototype.draw.call( this );

			return $.Deferred().resolve().promise();
		},

		/**
		 * Will resize the input box to fit its current content.
		 *
		 * @protected
		 */
		_resizeInput: function() {
			this.$input.inputautoexpand( {
				expandWidth: false, // TODO: make this optional on valueview level
				expandHeight: true,
				suppressNewLine: true // TODO: make this optional/leave it to parser options
			} );
		},

		/**
		 * @inheritdoc
		 */
		focus: function() {
			// Move text cursor to the end of the textarea:
			this.$input.focusAt( 'end' );
		},

		/**
		 * @inheritdoc
		 */
		blur: function() {
			this.$input.blur();
		}
	} );

	return vv.experts.StringValue;

}( jQuery, jQuery.valueview ) );
