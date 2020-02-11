<template>
	<div class="wb-ui-processdialog-header">
		<!-- @slot This slot should be filled with a "safe" action, e.g. cancel -->
		<slot name="safeAction">
			<div />
		</slot>
		<h1 class="wb-ui-processdialog-header__title">
			<!-- @slot This slot should be filled with the title of the dialog -->
			<slot name="title">
				<span />
			</slot>
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

/**
 * A component to be used as header of dialogs in which users make their
 * way through a process - i.e. there is a primary action "forward" and
 * an alternative second one.
 */
@Component
export default class ProcessDialogHeader extends Vue {}
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

		@include h5();
	}
}
</style>
