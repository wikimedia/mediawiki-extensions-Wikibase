/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, vv, time ) {
	'use strict';

	var Time = time.Time,
		timeSettings = time.settings;

	var PARENT = vv.experts.StringValue;

	/**
	 * Valueview expert handling input of time values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 *
	 * @option {Object} messages Default messages used by the widget. The keys correspond to
	 *         mediaWiki message keys since these will be picked when in MediaWiki environment and
	 *         the mediaWiki JavaScript object has been passed to the expert constructor.
	 */
	vv.experts.TimeInput = vv.expert( 'TimeInput', PARENT, function() {
		PARENT.apply( this, arguments );

		var self = this;

		this.preview = new vv.ExpertExtender.Preview( function() {
			return self.viewState().getFormattedValue();
		} );

		var precisionMsgKey = 'valueview-expert-timeinput-precision';
		var $precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append( $( '<div/>' ).text( this._messageProvider.getMessage( precisionMsgKey ) ) );

		this.precisionRotator = new vv.ExpertExtender.Listrotator(
			this.uiBaseClass + '-precision',
			getPrecisionValues(),
			$.proxy( this._onRotatorChange, this ),
			function() {
				var value = self.viewState().value();
				return value && value.getValue().precision();
			}
		);

		var calendarMsgKey = 'valueview-expert-timeinput-calendar';
		var $calendarContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-calendarcontainer' )
			.append( $( '<div/>' ).text( this._messageProvider.getMessage( calendarMsgKey ) ) );

		this.calendarRotator = new vv.ExpertExtender.Listrotator(
			this.uiBaseClass + '-calendar',
			getCalendarValues( this._messageProvider ),
			$.proxy( this._onRotatorChange, this ),
			function() {
				var value = self.viewState().value();
				return value && value.getValue().calendar();
			}
		);

		var inputExtender = new vv.ExpertExtender(
			this.$input,
			[
				this.preview,
				new vv.ExpertExtender.CalendarHint(
					this._messageProvider,
					function() {
						var value = self.viewState().value();
						return value && value.getValue();
					},
					function( value ) {
						// FIXME: Do not use private function:
						self.calendarRotator.rotator._setValue( value );
						self.calendarRotator.rotator.value( value );
						self.calendarRotator.rotator.activate();
					}
				),
				new vv.ExpertExtender.Toggler(
					this._messageProvider,
					$precisionContainer.add( $calendarContainer )
				),
				new vv.ExpertExtender.Container(
					$precisionContainer,
					this.precisionRotator
				),
				new vv.ExpertExtender.Container(
					$calendarContainer,
					this.calendarRotator
				)
			]
		);

		this.addExtension( inputExtender );
	}, {

		/**
		 * Default options
		 * @type {Object}
		 */
		_options: {
			messages: {
				'valueview-expert-timeinput-precision': 'Precision',
				'valueview-expert-timeinput-calendar': 'Calendar'
			}
		},

		/**
		 * The preview widget.
		 * @type {jQuery.valueview.ExpertExtender.Preview}
		 */
		preview: null,

		/**
		 * @type {jQuery.valueview.ExpertExtender.Listrotator}
		 */
		precisionRotator: null,

		/**
		 * @type {jQuery.valueview.ExpertExtender.Listrotator}
		 */
		calendarRotator: null,

		_onRotatorChange: function() {
			this._viewNotifier.notify( 'change' );
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this.preview = null;
			this.precisionRotator = null;
			this.calendarRotator = null;

			PARENT.prototype.destroy.call( this ); // empties viewport
		},

		/**
		 * @see jQuery.valueview.Expert.valueCharacteristics
		 */
		valueCharacteristics: function() {
			var options = {},
				precision = this.precisionRotator && this.precisionRotator.getValue() || null,
				calendarname = this.calendarRotator && this.calendarRotator.getValue() || null;

			if( precision !== null ) {
				options.precision = precision;
			}
			if( calendarname !== null ) {
				options.calendar = calendarNameToUri( calendarname );
			}

			return options;
		}
	} );

	/**
	 * @return {Object[]} [{ value: <{number}>, label: <{string}>}, ...]
	 */
	function getPrecisionValues() {
		var precisionValues = [];
		$.each( timeSettings.precisiontexts, function( i, text ) {
			if( i <= Time.PRECISION.DAY ) {
				// TODO: Remove this check as soon as time values are supported.
				precisionValues.unshift( { value: i, label: text } );
			}
		} );
		return precisionValues;
	}

	/**
	 * @param {util.MessageProvider} messageProvider
	 * @return @return {Object[]} [{ value: <{string}>, label: <{string}>}, ...]
	 */
	function getCalendarValues( messageProvider ) {
		var calendarValues = [];
		$.each( timeSettings.calendarnames, function( calendarKey, calendarTerms ) {
			var label = messageProvider.getMessage(
				'valueview-expert-timevalue-calendar-' + calendarTerms[0].toLowerCase()
			) || calendarTerms[0];
			calendarValues.push( { value: calendarTerms[0], label: label } );
		} );
		return calendarValues;
	}

	/**
	 * @param {string} calendarname
	 * @return {time.Time}
	 */
	function calendarNameToUri( calendarname ) {
		return new Time( { calendarname: calendarname, precision: 0, year: 0 } ).calendarURI();
	}

}( jQuery, jQuery.valueview, time ) );
