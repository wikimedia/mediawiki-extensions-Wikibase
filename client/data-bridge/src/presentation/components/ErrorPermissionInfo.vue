<template>
	<div class="wb-db-error-permission-info">
		<div
			class="wb-db-error-permission-info__header"
			v-html="messageHeader"
		/>
		<a
			class="wb-db-error-permission-info__toggle"
			:class="[ `wb-db-error-permission-info__toggle--${ infoIsExpanded ? 'open' : 'closed' }` ]"
			@click="toggleInfo"
		>
			{{ this.$messages.get( this.$messages.KEYS.PERMISSIONS_MORE_INFO ) }}
		</a>
		<div
			class="wb-db-error-permission-info__body"
			v-html="messageBody"
			v-if="infoIsExpanded"
		/>
	</div>
</template>

<script lang="ts">
import {
	Prop,
	Vue,
} from 'vue-property-decorator';
import Component from 'vue-class-component';

/**
 * A component used to illustrate permission errors which happened when
 * checking the user's authorization to perform an action.
 */
@Component
export default class ErrorPermissionInfo extends Vue {
	public infoIsExpanded = false;

	/**
	 * Flag to decide if the component is to be shown in an expanded
	 * state initially.
	 */
	@Prop( { required: false, default: false, type: Boolean } )
	public expandedByDefault!: boolean;

	/**
	 * The mark-up to show in the header.
	 * Careful, this value will not be HTML-escaped for you to allow
	 * for formatting of the content.
	 */
	@Prop( { required: true } )
	private readonly messageHeader!: string;

	/**
	 * The mark-up to show in the body.
	 * Careful, this value will not be HTML-escaped for you to allow
	 * for formatting of the content.
	 */
	@Prop( { required: true } )
	private readonly messageBody!: string;

	public created(): void {
		this.infoIsExpanded = this.expandedByDefault;
	}

	private toggleInfo(): void {
		this.infoIsExpanded = !this.infoIsExpanded;
	}
}
</script>

<style lang="scss">
.wb-db-error-permission-info {

	// TODO could use a variant of IconMessageBox
	padding-left: 2em;
	background: $svg-info no-repeat top left;

	@include body-responsive();

	&__header,
	p,
	ul {
		margin-bottom: $base-spacing-unit;
	}

	li:not( :first-child ) {
		margin-top: $margin-top-li;
	}

	&__toggle {
		$background-size: 14px;
		display: block;
		margin: $base-spacing-unit 0 2*$base-spacing-unit;
		padding-left: $background-size + 4px;
		background-position: top left;
		background-repeat: no-repeat;
		background-size: $background-size $background-size;
	}

	a,
	&__toggle {
		color: $color-primary;
		cursor: pointer;
	}

	a:hover,
	&__toggle:hover {
		color: $color-primary--hover;
	}

	&__toggle--closed {
		background-image: $svg-expand;
	}

	&__toggle--open {
		background-image: $svg-collapse;
	}
}
</style>
