/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
jQuery.valueview.BifidExpert = ( function( dv, $, Expert ) {
	'use strict';

	var PARENT = Expert;

	/**
	 * Abstract definition of a Valueview expert whose responsibilities are shared by two valueview
	 * experts; one taking over during edit mode, one being responsible while in static mode.
	 *
	 * TODO: Get rid of this by using value formatters in jQuery.valueview by injecting a
	 *  formatter factory. Experts will then no longer be responsible for serving a non-edit
	 *  representation of the value and there won't be any purpose for the BifidExpert anymore.
	 *
	 * @since 0.1
	 *
	 * @abstract
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	return dv.util.inherit( PARENT, {
		/**
		 * Constructor for the valueview expert responsible during static mode.
		 * @type Function
		 */
		_staticExpert: null,

		/**
		 * Options map, the constructor of "_staticExpert" will be initialized with.
		 */
		_staticExpertOptions: null,

		/**
		 * Constructor for the valueview expert responsible during edit mode.
		 * @type Function
		 */
		_editableExpert: null,

		/**
		 * Options map, the constructor of "_editableExpert" will be initialized with.
		 */
		_editableExpertOptions: null,

		/**
		 * The expert currently used internally. Either an instance of the constructor given in the
		 * "_editableExpert" or "_staticExpert" field.
		 * @type jQuery.valueview.Expert
		 */
		_currentExpert: null,

		/**
		 * @see jQuery.valueview.Expert._init
		 */
		_init: function() {
			this._updateExpert();
		},

		/**
		 * Will check whether the current expert is the right one for the related view's current
		 * mode. If not, the expert will be changed. Returns whether or not the expert has been
		 * updated.
		 *
		 * @since 0.1
		 *
		 * @return boolean
		 */
		_updateExpert: function() {
			var NewExpertConstructor, newExpertOptions;

			if( this._viewState.isInEditMode() ) {
				NewExpertConstructor = this._editableExpert;
				newExpertOptions = this._editableExpertOptions || {};
			} else {
				NewExpertConstructor = this._staticExpert;
				newExpertOptions = this._staticExpertOptions || {};
			}

			if( !this._currentExpert // first call
				|| this._currentExpert.constructor !== NewExpertConstructor
			) {
				var rawValue = null;

				if( this._currentExpert ) {
					rawValue = this._currentExpert.rawValue();

					// Destroy old expert which was responsible during previous state:
					this._currentExpert.destroy();
					this.$viewPort.empty();
				}

				// Instantiate new expert, responsible during current state:
				this._currentExpert = new NewExpertConstructor(
					this.$viewPort,
					this._viewState,
					this._viewNotifier,
					// Pass common and individual expert options:
					$.extend( {}, this._options, newExpertOptions )
				);
				this._currentExpert.rawValue( rawValue );

				return true;
			}
			return false;
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this.$viewPort = null;
			this._viewState = null;
		},

		/**
		 * @see jQuery.valueview.Expert.valueCharacteristics
		 */
		valueCharacteristics: function() {
			return this._currentExpert.valueCharacteristics();
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 */
		_getRawValue: function() {
			return this._currentExpert.rawValue();
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 */
		_setRawValue: function( rawValue ) {
			return this._currentExpert.rawValue( rawValue );
		},

		/**
		 * @see jQuery.valueview.Expert.rawValueCompare
		 */
		rawValueCompare: function( rawValue1, rawValue2 ) {
			return this._currentExpert.rawValueCompare( rawValue1, rawValue2 );
		},

		/**
		 * @see jQuery.valueview.Expert.rawValueCompare
		 */
		draw: function() {
			if( !this._updateExpert() ) {
				// Current expert still the right one, no update, re-draw current expert.
				this._currentExpert.draw();
			}
		},

		/**
		 * @see jQuery.valueview.Expert.focus
		 */
		focus: function() {
			this._currentExpert.focus();
		},

		/**
		 * @see jQuery.valueview.Expert.blur
		 */
		blur: function() {
			this._currentExpert.blur();
		}
	} );

}( dataValues, jQuery, jQuery.valueview.Expert ) );
