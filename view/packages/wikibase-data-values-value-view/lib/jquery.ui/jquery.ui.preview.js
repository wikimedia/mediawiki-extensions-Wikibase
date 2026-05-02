( function () {
	'use strict';

/**
 * Preview widget whose visible content may be switched between a spinner animation and a value.
 * If the value to be set is empty, the widget will display an appropriate message.
 *
 * @class jQuery.ui.preview
 * @extends jQuery.Widget
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} [options]
 * @param {jQuery|null} [options.$input=null]
 *        Input element node. If specified, the preview will not be updated when the input element
 *        is cleared, e.g. if the input will be hidden, it is not necessary to update the value.
 * @param {Object} [options.messages=Object]
 *        Default messages to use.
 * @param {util.MessageProvider|null} [options.messageProvider=null]
 *        Message provider to fetch messages from instead of using the default messages.
 */
$.widget( 'ui.preview', {

	/**
	 * @see jQuery.Widget.options
	 * @protected
	 * @readonly
	 */
	options: {
		$input: null,
		messages: {
			label: 'will be displayed as:',
			novalue: 'no valid value recognized'
		},
		messageProvider: null
	},

	/**
	 * The node of the previewed value.
	 *
	 * @property {jQuery}
	 * @readonly
	 */
	$value: null,

	/**
	 * @property {util.MessageProvider}
	 * @private
	 */
	_messageProvider: null,

	/**
	 * @see jQuery.Widget._create
	 * @protected
	 */
	_create: function() {
		var hashBasedMessageProvider = new util.HashMessageProvider( this.options.messages );
		if ( this.options.messageProvider ) {
			this._messageProvider = new util.CombiningMessageProvider(
				this.options.messageProvider,
				hashBasedMessageProvider
			);
		} else {
			this._messageProvider = hashBasedMessageProvider;
		}

		this.element
		.addClass( this.widgetBaseClass )
		.append(
			$( '<div/>' )
			.addClass( this.widgetBaseClass + '-label' )
			.text( this._messageProvider.getMessage( 'label' ) )
		);

		this.$value = $( '<div/>' )
		.addClass( this.widgetBaseClass + '-value' )
		.appendTo( this.element );

		this.update( null );
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		this.$value.remove();

		this.element
		.children( this.widgetBaseClass + '-label' )
		.removeClass( this.widgetBaseClass );

		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * Updates the previewed value.
	 *
	 * @param {string|null} value
	 */
	update: function( value ) {
		// No need to update the preview when the input value is clear(ed) since the preview
		// will be hidden anyway.
		if ( this.options.$input && this.options.$input.val() === '' ) {
			return;
		}

		this.$value.toggleClass( this.widgetBaseClass + '-novalue', value === null );

		if ( value === null ) {
			this.$value.text( this._messageProvider.getMessage( 'novalue' ) );
		} else {
			this.$value.html( value );
		}
	},

	/**
	 * Shows a spinner symbol instead of any preview.
	 */
	showSpinner: function() {
		this.$value.empty().append( $( '<span/>' ).addClass( 'small-spinner' ) );
	}

} );

}() );
