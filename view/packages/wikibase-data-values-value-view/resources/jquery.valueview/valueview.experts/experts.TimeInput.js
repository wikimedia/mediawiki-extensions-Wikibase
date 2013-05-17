/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
// TODO: Remove mediaWiki dependency
( function( dv, vp, $, vv, time, mw ) {
	'use strict';

	var Time = time.Time,
		timeSettings = time.settings;

	var PARENT = vv.Expert;

	/**
	 * Valueview expert handling input of time values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.TimeInput = vv.expert( 'timeinput', PARENT, {
		/**
		 * The the input element's node.
		 * @type {jQuery}
		 */
		$input: null,

		/**
		 * Caches a new value (or null for no value) set by _setRawValue() until draw() displaying
		 * the new value has been called. The use of this, basically, is a structural improvement
		 * which allows moving setting the displayed value to the draw() method which is supposed to
		 * handle all visual manners.
		 * @type {time.Time|null|false}
		 */
		_newValue: null,

		/**
		 * The preview section's node.
		 * @type {jQuery}
		 */
		$preview: null,

		/**
		 * The node of the previewed input value.
		 * @type {jQuery}
		 */
		$previewValue: null,

		/**
		 * Container node for precision input and label.
		 * @type {jQuery}
		 */
		$precisionContainer: null,

		/**
		 * Node of the widget used to specify the precision.
		 * @type {jQuery}
		 */
		$precision: null,

		/**
		 * Container node for calendar input and label.
		 * @type {jQuery}
		 */
		$calendarContainer: null,

		/**
		 * Node of the widget used to specify the calendar.
		 * @type {jQuery}
		 */
		$calendar: null,

		/**
		 * Node of the hint giving information about the automatically selected calendar.
		 * @type {jQuery}
		 */
		$calendarhint: null,

		/**
		 * @see jQuery.valueview.Expert._init
		 */
		_init: function() {
			var self = this;

			// TODO: Move preview out of the specific expert to a more generic place
			this.$preview = $( '<div/>' )
			.addClass( 'valueview-preview' )
			.append(
				$( '<div/>' )
				.addClass( 'valueview-preview-label' )
				.text( mw.msg( 'valueview-preview-label' ) )
			);

			this.$previewValue = $( '<div/>' )
			.addClass( 'valueview-preview-value' )
			.text( mw.msg( 'valueview-preview-novalue' ) )
			.appendTo( this.$preview );

			this.$precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append( $( '<div/>' ).text( mw.msg( 'valueview-expert-timeinput-precision' ) ) );

			var precisionValues = [];
			$.each( timeSettings.precisiontexts, function( i, text ) {
				precisionValues.push( { value: i, label: text } );
			} );

			this.$precision = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precision' )
			.listrotator( { values: precisionValues.reverse(), deferInit: true } )
			.on(
				'listrotatorauto.' + this.uiBaseClass + ' listrotatorselected.' + this.uiBaseClass,
				function( event ) {
					var overwrite = {};

					if( event.type === 'listrotatorauto' ) {
						overwrite.precision = undefined;
					}

					var value = self._updateValue( overwrite );

					if( event.type === 'listrotatorauto' ) {
						$( this ).data( 'listrotator' ).rotate( value.precision() );
					}
				}
			)
			.appendTo( this.$precisionContainer );

			this.$calendarContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-calendarcontainer' )
			.append( $( '<div/>' ).text( mw.msg( 'valueview-expert-timeinput-calendar' ) ) );

			var calendarValues = [];
			$.each( timeSettings.calendarnames, function( i, calendarTerms ) {
				calendarValues.push( { value: calendarTerms[0], label: calendarTerms[0] } );
			} );
			this.$calendar = $( '<div/>' )
			.listrotator( { values: calendarValues, deferInit: true } )
			.on(
				'listrotatorauto.' + this.uiBaseClass + ' listrotatorselected.' + this.uiBaseClass,
				function( event ) {
					var overwrite = {};

					if( event.type === 'listrotatorauto' ) {
						overwrite.calendarname = undefined;
					}

					var value = self._updateValue( overwrite );

					if( event.type === 'listrotatorauto' ) {
						$( this ).data( 'listrotator' ).rotate( value.calendarText() );
					}
				}
			)
			.appendTo( this.$calendarContainer );

			var $toggler = $( '<a/>' )
			.addClass( this.uiBaseClass + '-advancedtoggler' )
			.text( mw.msg( 'valueview-expert-advancedadjustments' ) );

			this.$calendarhint = $( '<div/>' )
			.addClass( this.uiBaseClass + '-calendarhint' )
			.append( $( '<span/>' ).addClass( this.uiBaseClass + '-calendarhint-message' ) )
			.append(
				$( '<a/>' )
				.addClass( this.uiBaseClass + '-calendarhint-switch ui-state-default' )
				.attr( 'href', 'javascript:void(0);' )
			);

			this.$input = $( '<input/>', {
				type: 'text',
				'class': this.uiBaseClass + '-input valueview-input'
			} )
			.appendTo( this.$viewPort )
			.eachchange( function( event, oldValue ) {
				var value = self.$input.data( 'timeinput' ).value();
				if( oldValue === '' && value === null || self.$input.val() === '' ) {
					self._updatePreview( null );
					self._updateCalendarHint();
				}
			} )
			.timeinput()
			// TODO: Move input extender out of here to a more generic place since it is not
			// TimeInput specific.
			.inputextender( {
				content: [ this.$preview, this.$calendarhint, $toggler, this.$precisionContainer, this.$calendarContainer ],
				initCallback: function() {
					self.$precision.data( 'listrotator' ).initWidths();
					self.$calendar.data( 'listrotator' ).initWidths();

					var $subjects = self.$precisionContainer.add( self.$calendarContainer );
					$subjects.css( 'display', 'none' );
					$toggler.toggler( { $subject: $subjects } );
				}
			} )
			.on( 'timeinputupdate.' + this.uiBaseClass, function( event, value ) {
				self._updatePreview( value );
				self._updateCalendarHint( value );
				if( value && value.isValid() ) {
					self.$precision.data( 'listrotator' ).rotate( value.precision() );
					self.$calendar.data( 'listrotator' ).rotate( value.calendarText() );
				}
				self._viewNotifier.notify( 'change' );
			} );

		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this.$precision.data( 'listrotator' ).destroy();
			this.$precision.remove();
			this.$precisionContainer.remove();

			this.$calendar.data( 'listrotator' ).destroy();
			this.$calendar.remove();
			this.$calendarContainer.remove();

			this.$calendarhint.remove();

			this.$previewValue.remove();
			this.$preview.remove();

			this.$input.data( 'inputextender' ).destroy();
			this.$input.data( 'timeinput' ).destroy();
			this.$input.remove();

			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Builds a time.Time object from the widget's current input and advanced adjustments.
		 *
		 * @param {Object} [overwrites] Values that should be used instead of the ones picked from
		 *        the input elements.
		 * @return {time.Time}
		 */
		_updateValue: function( overwrites ) {
			overwrites = overwrites || {};

			var options = {},
				precision = ( overwrites.hasOwnProperty( 'precision' ) )
					? overwrites.precision
					: this.$precision.data( 'listrotator' ).value(),
				calendarname = ( overwrites.hasOwnProperty( 'calendarname' ) )
					? overwrites.calendarname
					: this.$calendar.data( 'listrotator' ).value(),
				value = null;

			if( precision !== undefined ) {
				options.precision = precision;
			}

			if( calendarname !== undefined ) {
				options.calendarname = calendarname;
			}

			value = new Time( this.$input.val(), options );

			this._setRawValue( value );
			this._updatePreview( value );
			this._updateCalendarHint( value );
			this._viewNotifier.notify( 'change' );

			return value;
		},

		/**
		 * Updates the input value's preview.
		 * @since 0.1
		 *
		 * @param {time.Time|null} value
		 */
		_updatePreview: function( value ) {
			// No need to update the preview when the input value is clear(ed) since the preview
			// will be hidden anyway.
			if( this.$input.val() === '' ) {
				return;
			}

			if( value === null ) {
				this.$previewValue
				.addClass( 'valueview-preview-novalue' )
				.text( mw.msg( 'valueview-preview-novalue' ) )
			} else {
				this.$previewValue
				.removeClass( 'valueview-preview-novalue' )
				.text( value.text() )
			}
		},

		/**
		 * Updates the calendar hint message.
		 *
		 * @param {time.Time} [value] Message will get hidden when omitted.
		 */
		_updateCalendarHint: function( value ) {
			if( value && value.year() > 1581 && value.year() < 1930 && value.precision() > 10 ) {
				var self = this;

				var otherCalendar = ( value.calendarText() === timeSettings.calendarnames[0][0] )
					? timeSettings.calendarnames[1][0]
					: timeSettings.calendarnames[0][0];

				this.$calendarhint.children( '.' + this.uiBaseClass + '-calendarhint-message' )
				.text( mw.msg( 'valueview-expert-timeinput-calendarhint', value.calendarText() ) );

				this.$calendarhint.children( '.' + this.uiBaseClass + '-calendarhint-switch' )
				.off( 'click.' + this.uiBaseClass )
				.on( 'click.' + this.uiBaseClass, function( event ) {
					self.$calendar.data( 'listrotator' ).rotate( otherCalendar, function() {
						self._updateValue();
					} );
				} )
				.html( mw.msg( 'valueview-expert-timeinput-calendarhint-switch', otherCalendar ) );

				this.$calendarhint.show();
			} else {
				this.$calendarhint.hide();
			}
		},

		/**
		 * @see jQuery.valueview.Expert.parser
		 */
		parser: function() {
			return new vp.TimeParser();
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 *
		 * @return {time.Time|null}
		 */
		_getRawValue: function() {
			return ( this._newValue !== false )
				? this._newValue
				: this.$input.data( 'timeinput' ).value();
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 *
		 * @param {time.Time|null} time
		 */
		_setRawValue: function( time ) {
			if( !( time instanceof Time ) || !time.isValid() ) {
				time = null;
			}
			this._newValue = time;
		},

		/**
		 * @see jQuery.valueview.Expert.rawValueCompare
		 */
		rawValueCompare: function( time1, time2 ) {
			if( time2 === undefined ) {
				time2 = this._getRawValue();
			}

			if( time1 === null && time2 === null ) {
				return true;
			}

			if( !( time1 instanceof Time ) || !( time2 instanceof Time ) ) {
				return false;
			}

			return time1.isValid() && time2.isValid()
				&& time1.precision() === time2.precision()
				&& time1.iso8601() === time2.iso8601();
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			if( this._viewState.isDisabled() ) {
				this.$input.data( 'timeinput' ).disable();
			} else {
				this.$input.data( 'timeinput' ).enable();
			}

			if( this._newValue !== false ) {
				this.$input.data( 'timeinput' ).value( this._newValue );
				this._updatePreview( this._newValue );
				this._updateCalendarHint( this._newValue );
				if( this._newValue !== null ) {
					this.$precision.data( 'listrotator' ).value( this._newValue.precision() );
					this.$calendar.data( 'listrotator' ).value( this._newValue.calendarText() );
				}
				this._newValue = false;
			}
		},

		/**
		 * @see jQuery.valueview.Expert.focus
		 */
		focus: function() {
			this.$input.focusAt( 'end' );
		},

		/**
		 * @see jQuery.valueview.Expert.blur
		 */
		blur: function() {
			this.$input.blur();
		}
	} );

}( dataValues, valueParsers, jQuery, jQuery.valueview, time, mediaWiki ) );
