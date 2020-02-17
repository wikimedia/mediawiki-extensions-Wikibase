<template>
	<div class="wb-db-deprecated-statement">
		<IconMessageBox
			class="wb-db-deprecated-statement__message"
			type="notice"
			:inline="true"
		>
			<p
				class="wb-db-deprecated-statement__head"
				v-html="$messages.get( $messages.KEYS.DEPRECATED_STATEMENT_ERROR_HEAD, propertyLabel )"
			/>
			<p
				class="wb-db-deprecated-statement__body"
				v-html="$messages.get( $messages.KEYS.DEPRECATED_STATEMENT_ERROR_BODY, propertyLabel )"
			/>
		</IconMessageBox>
		<BailoutActions
			class="wb-db-deprecated-statement__bailout"
			:original-href="originalHref"
			:page-title="pageTitle"
		/>
	</div>
</template>

<script lang="ts">
import Component, { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import TermLabel from '@/presentation/components/TermLabel.vue';

/**
 * A component used to illustrate a deprecated statement error which happened when
 * the user tried to edit a statement with a deprecated rank.
 */
@Component( {
	components: {
		IconMessageBox,
		BailoutActions,
	},
} )
export default class ErrorDeprecatedStatement extends mixins( StateMixin ) {
	public get propertyLabel(): HTMLElement {
		return new TermLabel( {
			propsData: {
				term: this.rootModule.getters.targetLabel,
			},
		} ).$mount().$el as HTMLElement;
	}

	public get pageTitle(): string {
		return this.rootModule.state.pageTitle;
	}

	public get originalHref(): string {
		return this.rootModule.state.originalHref;
	}
}
</script>

<style lang="scss">
.wb-db-deprecated-statement {
	@include errorBailout();
}
</style>
