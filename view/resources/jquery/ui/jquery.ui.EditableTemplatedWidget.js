/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function () {
	'use strict';

	require( './jquery.ui.closeable.js' );
	var PARENT = $.ui.TemplatedWidget;

	/**
	 * TemplatedWidget enhanced with editing capabilities.
	 * @constructor
	 * @abstract
	 * @extends jQuery.ui.TemplatedWidget
	 *
	 * @option {*} [value]
	 *
	 * @event afterstartediting
	 *        Triggered after having started the widget's edit mode and edit mode has been rendered.
	 *        - {jQuery.Event}
	 *
	 * @event afterstopediting
	 *        Triggered after having stopped the widget's edit mode and non-edit mode is redrawn.
	 *        - {jQuery.Event}
	 *        - {boolean} dropValue
	 *          Whether the widget's value has been reset to the one from before starting edit mode.
	 *
	 * @event change
	 *        Triggered whenever the widget's value is changed.
	 *        - {jQuery.Event} event
	 *
	 * @event toggleerror
	 *        Triggered when an error occurred or has been resolved.
	 *        - {jQuery.Event}
	 *        - {Error|undefined}
	 */
	$.widget( 'ui.EditableTemplatedWidget', PARENT, {
		/**
		 * @see jQuery.ui.TemplatedWidget.options
		 */
		options: $.extend( true, {}, PARENT.prototype.options, {
			value: null
		} ),

		/**
		 * @see jQuery.ui.TemplatedWidget._create
		 */
		_create: function () {
			this.element.data( 'EditableTemplatedWidget', this );
			PARENT.prototype._create.call( this );
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.destroy
		 */
		destroy: function () {
			this.element.removeClass( 'wb-edit' );
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Starts the widget's edit mode.
		 *
		 * @return {Object} jQuery.Promise
		 *         No resolved parameters.
		 *         Rejected parameters:
		 *         - {Error}
		 */
		startEditing: function () {
			if ( this.isInEditMode() ) {
				return $.Deferred().resolve().promise();
			}

			var self = this;

			this.element.addClass( 'wb-edit' );

			return this._startEditing()
			.done( function () {
				self._trigger( 'afterstartediting' );
			} );
		},

		_startEditing: function () {
			return $.Deferred().resolve().promise();
		},

		/**
		 * Stops the widget's edit mode.
		 *
		 * @param {boolean} dropValue
		 * @return {Object} jQuery.Promise
		 *         Resolved parameters:
		 *         - {boolean} dropValue
		 *         Rejected parameters:
		 *         - {Error}
		 */
		stopEditing: function ( dropValue ) {
			if ( !this.isInEditMode() ) {
				return $.Deferred().resolve().promise();
			}

			this.element.removeClass( 'wb-edit' );

			var self = this;
			return this._stopEditing( dropValue )
			.done( function () {
				self.enable();
				self._trigger( 'afterstopediting', null, [ dropValue ] );
			} )
			.fail( function ( error ) {
				self.setError( error );
			} );
		},

		_stopEditing: function ( dropValue ) {
			return $.Deferred().resolve().promise();
		},

		/**
		 * Returns whether the widget is in edit mode.
		 *
		 * @return {boolean}
		 */
		isInEditMode: function () {
			return this.element.hasClass( 'wb-edit' );
		},

		/**
		 * Sets/Gets the widget's current value.
		 * When the widget is in edit mode, this.option( 'value' ) may be used to retrieve the widget's
		 * value from before edit mode has been started.
		 *
		 * @param {*} [value]
		 * @return {*|undefined}
		 */
		value: util.abstractMember,

		/**
		 * Toggles error state.
		 *
		 * @param {Error} [error]
		 */
		setError: function ( error ) {
			if ( error ) {
				this.element.addClass( 'wb-error' );
				this._trigger( 'toggleerror', null, [ error ] );
			} else {
				this.removeError();
				this._trigger( 'toggleerror', null, [ null ] );
			}
		},

		/**
		 * Removes error state without triggering an event.
		 */
		removeError: function () {
			this.element.removeClass( 'wb-error' );
		},

		/**
		 * Sets or removes notification.
		 *
		 * @param {jQuery} [$content]
		 * @param {string} [additionalCssClasses]
		 * @return {jQuery|null}
		 */
		notification: function ( $content, additionalCssClasses ) {
			if ( !this._$notification ) {
				this._$notification = $( '<div>' ).closeable( {
					encapsulate: true
				} );
			}
			this._$notification.data( 'closeable' ).setContent( $content, additionalCssClasses );
			return this._$notification;
		},

		/**
		 * Get a help message related to editing
		 *
		 * @return {Object} jQuery promise
		 *         Resolved parameters:
		 *         - {string}
		 *         No rejected parameters.
		 */
		getHelpMessage: function () {
			return $.Deferred().resolve( this.options.helpMessage ).promise();
		},

		/**
		 * @var {null|Function} A function notifying about an error or null if the toolbar should notify
		 */
		doErrorNotification: null
	} );

}() );
