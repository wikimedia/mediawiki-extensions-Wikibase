/**
 * Preview widget whose visible content may be switched between a spinner animation and a value.
 * If the value to be set is empty, the widget will display an appropriate message.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @option {jQuery} [$input] Input element node. If specified, the preview will not be updated when
 *         the input element is cleared, e.g. if the input will be hidden, it is not necessary to
 *         update the value.
 *
 * @option {Object} [messages] Default messages to use.
 *
 * @option {util.MessageProvider} [messageProvider] Message provider to fetch messages from instead
 *         of using the default messages.
 */
( function( $ ) {
	'use strict';

	$.widget( 'ui.preview', {

		/**
		 * @type {Object}
		 */
		options: {
			$input: null,
			messages: {
				'label': 'will be displayed as:',
				'novalue': 'no valid value recognized'
			},
			messageProvider: null
		},

		/**
		 * The node of the previewed value.
		 * @type {jQuery}
		 */
		$value: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			if( this.options.messageProvider ) {
				this.options.messageProvider.setDefaultMessages( this.options.messages );
			}

			this.element
			.addClass( this.widgetBaseClass )
			.append(
				$( '<div/>' )
				.addClass( this.widgetBaseClass + '-label' )
				.text( this._getMessage( 'label' ) )
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
			if( this.options.$input && this.options.$input.val() === '' ) {
				return;
			}

			if( value === null ) {
				this.$value
				.addClass( this.widgetBaseClass + '-novalue' )
				.text( this._getMessage( 'novalue' ) );
			} else {
				this.$value
				.removeClass( this.widgetBaseClass + '-novalue' )
				.text( value );
			}
		},

		/**
		 * Shows a spinner symbol instead of any preview.
		 */
		showSpinner: function() {
			this.$value.empty().append( $( '<span/>' ).addClass( 'small-spinner' ) );
		},

		/**
		 * Either retrieves a message from the message provider (if set) or returns the default
		 * message.
		 *
		 * @param {string} key
		 * @return {string|null}
		 */
		_getMessage: function( key ) {
			return this.options.messageProvider
				? this.options.messageProvider.getMessage( key )
				: this.options.messages[key];
		}

	} );

} )( jQuery );
