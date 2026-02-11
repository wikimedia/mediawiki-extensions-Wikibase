<template>
	<wikibase-wbui2025-editable-no-value-some-value-snak-value
		:snak-key="snakKey"
		:removable="removable"
		:disabled="disabled"
		@remove-snak="$emit( 'remove-snak', snakKey )"
	>
		<cdx-text-input
			ref="inputElement"
			v-model="textvalue"
			autocapitalize="off"
			:class="className"
			:disabled="disabled"
			@focus="handleFocus"
		></cdx-text-input>
		<cdx-popover
			v-model:open="showPopup"
			:use-close-button="true"
			:use-primary-action="false"
			:use-default-action="false"
			:render-in-place="true"
			:anchor="inputElement"
			placement="bottom-end"
			@update:open="closePopup"
		>
			<template #header>
				<p>{{ $i18n( 'wikibase-wbui2025-editable-snak-value-preview-label' ) }}</p>
				<div class="cdx-popover__header__button-wrapper">
					<cdx-button
						class="cdx-popover__header__close-button"
						weight="quiet"
						type="button"
						:aria-label="$i18n( 'cdx-popover-close-button-label' )"
						@click="closePopup"
					>
						<cdx-icon :icon="cdxIconClose"></cdx-icon>
					</cdx-button>
				</div>
			</template>
			<div class="time-options">
				<p v-html="formattedValue"></p>
				<p class="option-and-select">
					<b>{{ $i18n( 'valueview-expert-timeinput-precision' ).text() }}</b>{{ currentPrecision }}
					<select v-model="selectedPrecision">
						<option
							v-for="option in getPrecisionSelectValues()"
							:key="option.value"
							:value="option.value"
						>
							{{ option.label }}
						</option>
					</select>
				</p>
				<p class="option-and-select">
					<b>{{ $i18n( 'valueview-expert-timeinput-calendar' ).text() }}</b>{{ currentCalendar }}
					<select v-model="selectedCalendar">
						<option
							v-for="option in getCalendarSelectValues()"
							:key="option.value"
							:value="option.value"
						>
							{{ option.label }}
						</option>
					</select>
				</p>
			</div>
		</cdx-popover>
	</wikibase-wbui2025-editable-no-value-some-value-snak-value>
</template>

<script>
/**
 * Hard-coded list of precisions matching the precisions on the server-side.
 *
 * @see view/lib/wikibase-data-values/src/values/TimeValue.js
 * @see repo/includes/Parsers/MwTimeIsoParser.php
 * @type {string[]}
 */
const precisions = [
	'YEAR1G',
	'YEAR100M',
	'YEAR10M',
	'YEAR1M',
	'YEAR100K',
	'YEAR10K',
	'YEAR1K',
	'YEAR100',
	'YEAR10',
	'YEAR',
	'MONTH',
	'DAY'
];

/**
 * Hard-coded list of calendars and their Wikidata items.
 *
 * @see view/lib/wikibase-data-values/src/values/TimeValue.js
 * @type {{ id: string, item: string }[]}
 */
const calendars = [
	{ id: 'JULIAN', item: 'http://www.wikidata.org/entity/Q1985786' },
	{ id: 'GREGORIAN', item: 'http://www.wikidata.org/entity/Q1985727' }
];
const { computed, defineComponent, ref } = require( 'vue' );
const { mapWritableState } = require( 'pinia' );
const { CdxPopover, CdxButton, CdxIcon, CdxTextInput } = require( '../../../codex.js' );
const { cdxIconClose } = require( '../icons.json' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const WikibaseWbui2025EditableNoValueSomeValueSnakValue = require( './editableNoValueSomeValueSnakValue.vue' );

const getCalendarIdByItem = ( item ) => calendars.find( ( c ) => c.item === item ).id;

// Messages that can be used here:
// * valueview-expert-timeinput-precision-year1g
// * valueview-expert-timeinput-precision-year100m
// * valueview-expert-timeinput-precision-year10m
// * valueview-expert-timeinput-precision-year1m
// * valueview-expert-timeinput-precision-year100k
// * valueview-expert-timeinput-precision-year10k
// * valueview-expert-timeinput-precision-year1k
// * valueview-expert-timeinput-precision-year100
// * valueview-expert-timeinput-precision-year10
// * valueview-expert-timeinput-precision-year
// * valueview-expert-timeinput-precision-month
// * valueview-expert-timeinput-precision-day
const getPrecisionLabelForPrecisionId = ( id ) => mw.msg(
	'valueview-expert-timeinput-precision-' + id.toLowerCase()
);

const getPrecisionSelectValues = () => {
	const selectValues = [];
	precisions.forEach( ( precisionId, precisionIndex ) => {
		selectValues.unshift( { value: precisionIndex, label: getPrecisionLabelForPrecisionId( precisionId ) } );
	} );
	selectValues.unshift( { value: 'automatic', label: mw.msg( 'wikibase-wbui2025-timeinput-precision-automatic' ) } );
	return selectValues;
};

// Messages that can be used here:
// * valueview-expert-timevalue-calendar-julian
// * valueview-expert-timevalue-calendar-gregorian
const getLabelForCalendarId = ( id ) => mw.msg(
	'valueview-expert-timevalue-calendar-' + id.toLowerCase()
);

const getCalendarSelectValues = () => {
	const selectValues = [];
	calendars.forEach( ( calendar ) => {
		selectValues.unshift( { value: calendar.id, label: getLabelForCalendarId( calendar.id ) } );
	} );
	selectValues.unshift( { value: 'automatic', label: mw.msg( 'wikibase-wbui2025-timeinput-precision-automatic' ) } );
	return selectValues;
};

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableTimeSnakValue',
	components: {
		CdxPopover,
		CdxButton,
		CdxIcon,
		CdxTextInput,
		WikibaseWbui2025EditableNoValueSomeValueSnakValue
	},
	props: {
		snakKey: {
			type: String,
			required: true
		},
		removable: {
			type: Boolean,
			required: false,
			default: false
		},
		disabled: {
			type: Boolean,
			required: true
		},
		className: {
			type: String,
			required: false,
			default: 'wikibase-wbui2025-editable-snak-value-input'
		}
	},
	emits: [ 'remove-snak' ],
	setup( props ) {
		/*
		 * Usually we use the Options API to map state and actions. In this case, we need a parameterised
		 * store - we pass in the snakHash to make a snak-specific store. This forces us to use
		 * the Composition API to initialise the component.
		 */
		const editSnakStoreGetter = wbui2025.store.useEditSnakStore( props.snakKey );
		const formattedValue = ref( wbui2025.store.snakValueHtmlForHash( editSnakStoreGetter().hash ) );
		const computedProperties = mapWritableState( editSnakStoreGetter, [
			'textvalue',
			'precision',
			'calendar',
			'valueStrategy'
		] );
		const inputElement = ref();
		const formatValue = () => {
			editSnakStoreGetter().valueStrategy.getParsedValue().then( ( value ) => {
				if ( value === null ) {
					formattedValue.value = mw.msg( 'wikibase-parse-error-time' );
					return;
				}
				wbui2025.api.renderSnakValueHtml( value, editSnakStoreGetter().property ).then( ( formatValueOutput ) => {
					formattedValue.value = formatValueOutput;
				} );
			} );
		};
		return {
			inputElement,
			formattedValue,
			textvalue: computed( computedProperties.textvalue ),
			precision: computed( computedProperties.precision ),
			calendar: computed( computedProperties.calendar ),
			valueStrategy: computed( computedProperties.valueStrategy ),
			debouncedTriggerFormatAndParse: mw.util.debounce( formatValue, 300 ),
			getPrecisionSelectValues,
			getCalendarSelectValues
		};
	},
	data() {
		return {
			cdxIconClose,
			showPopup: false,
			hasBeenEdited: false,
			initialState: true,
			initialValue: undefined,
			selectedPrecision: 'automatic',
			selectedCalendar: 'automatic'
		};
	},
	computed: {
		currentPrecision() {
			const value = this.valueStrategy.peekDataValue();
			if ( !value || value.value.precision === undefined ) {
				return undefined;
			}
			return getPrecisionLabelForPrecisionId( precisions[ value.value.precision ] );
		},
		currentCalendar() {
			const value = this.valueStrategy.peekDataValue();
			if ( !value || value.value.calendarmodel === undefined ) {
				return undefined;
			}
			return getLabelForCalendarId( getCalendarIdByItem( value.value.calendarmodel ) );
		}
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.focus();
		},
		closePopup() {
			if ( !this.showPopup ) {
				this.$refs.inputElement.blur();
			}
			this.showPopup = false;
		},
		handleFocus() {
			this.showPopup = true;
		}
	},
	watch: {
		textvalue: {
			handler( newValue ) {
				if ( newValue === undefined ) {
					return;
				}
				/**
				 * We need to know if the user has updated the input. As soon as the input
				 * is updated with a new value, we reset precision and calendar to 'undefined' so
				 * that they are automatically derived by the parsing. Until the input is changed,
				 * we're still using the values for precision and calendar loaded from the original
				 * parsed value.
				 */
				if ( this.initialState ) {
					this.initialValue = newValue;
					this.initialState = false;
				} else {
					if ( !this.hasBeenEdited && newValue !== this.initialValue ) {
						this.hasBeenEdited = true;
						this.precision = undefined;
						this.calendar = undefined;
					}
				}
				this.debouncedTriggerFormatAndParse( newValue );
			},
			immediate: true
		},
		selectedPrecision: {
			handler( newPrecision ) {
				this.precision = ( newPrecision === 'automatic' ? undefined : newPrecision );
				this.debouncedTriggerFormatAndParse( undefined );
			}
		},
		selectedCalendar: {
			handler( newCalendar ) {
				this.calendar = ( newCalendar === 'automatic' ? undefined : newCalendar );
				this.debouncedTriggerFormatAndParse( undefined );
			}
		}
	} }
);
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-edit-statement-snak-value {

	.wikibase-wbui2025-snak-value {

		.cdx-text-input {
			width: 100%;
		}

		.content .cdx-popover__header p {
			padding: 0;
			margin: 0;
			font-size: 0.875rem;
			line-height: 1.4rem;
			letter-spacing: -0.00263rem;
		}

		.cdx-popover__header__button-wrapper {
			position: absolute;
			top: @spacing-25;
			right: @spacing-50;
		}

		.cdx-popover {
			padding: 0.75rem @spacing-100 @spacing-50 @spacing-100;
		}

		div.time-options {
			p.option-and-select {
				justify-content: space-between;
				display: flex;
				gap: 5px;

				select {
					margin-left: auto;
					width: 6rem;
					overflow: hidden;
					text-overflow: ellipsis;
				}
			}
		}

	}
}
</style>
