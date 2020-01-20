<template>
	<div class="wb-ui-processdialog-header">
		<!-- @slot This slot should be filled with a "safe" action, e.g. cancel -->
		<slot name="safeAction">
			<div />
		</slot>
		<h1 class="wb-ui-processdialog-header__title">
			{{ title }}
		</h1>
		<!-- @slot This slot should be filled with the primary action, e.g. publish -->
		<slot name="primaryAction">
			<div />
		</slot>
	</div>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

/**
 * A component to be used as header of dialogs in which users make their
 * way through a process - i.e. there is a primary action "forward" and
 * an alternative second one.
 */
@Component
export default class ProcessDialogHeader extends Vue {
	/**
	 * The title to be shown in the center of the header
	 */
	@Prop( { required: true, type: String } )
	public title!: string;
}
</script>

<style lang="scss">
.wb-ui-processdialog-header {
	display: flex;
	justify-content: space-between;
	box-sizing: border-box;
	border-bottom: 1px solid $wmui-color-base70;

	&__title {
		display: inline-flex;
		align-items: center;
		font-size: $font-size-dialog-process-title;
		font-weight: bold;
	}
}
</style>
