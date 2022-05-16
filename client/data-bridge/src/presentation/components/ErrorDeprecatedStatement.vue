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
import { createApp, defineComponent } from 'vue';
import StateMixin from '@/presentation/StateMixin';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import TermLabel from '@/presentation/components/TermLabel.vue';

/**
 * A component used to illustrate a deprecated statement error which happened when
 * the user tried to edit a statement with a deprecated rank.
 */
export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'ErrorDeprecatedStatement',
	components: {
		IconMessageBox,
		BailoutActions,
	},
	computed: {
		propertyLabel(): HTMLElement {
			return createApp(
				TermLabel,
				{
					term: this.rootModule.getters.targetLabel,
					inLanguage: this.$inLanguage,
				},
			).mount( document.createElement( 'span' ) ).$el;
		},
		pageTitle(): string {
			return this.rootModule.state.pageTitle;
		},
		originalHref(): string {
			return this.rootModule.state.originalHref;
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-deprecated-statement {
	@include errorBailout();
}
</style>
