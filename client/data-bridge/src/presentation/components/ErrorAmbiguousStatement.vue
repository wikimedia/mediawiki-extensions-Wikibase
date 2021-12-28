<template>
	<div class="wb-db-ambiguous-statement">
		<IconMessageBox
			class="wb-db-ambiguous-statement__message"
			type="notice"
			:inline="true"
		>
			<p class="wb-db-ambiguous-statement__head">
				{{ $messages.getText(
					$messages.KEYS.AMBIGUOUS_STATEMENT_ERROR_HEAD,
				) }}
			</p>
			<p
				class="wb-db-ambiguous-statement__body"
				v-html="$messages.get( $messages.KEYS.AMBIGUOUS_STATEMENT_ERROR_BODY, propertyLabel )"
			/>
		</IconMessageBox>
		<BailoutActions :original-href="originalHref" :page-title="pageTitle" />
	</div>
</template>

<script lang="ts">
import Vue, { VueConstructor } from 'vue';
import StateMixin from '@/presentation/StateMixin';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import TermLabel from '@/presentation/components/TermLabel.vue';

/**
 * A component used to illustrate an ambiguous statement error which happened when
 * the user tried to edit a statement group with more than one value.
 */
export default ( Vue as VueConstructor<Vue & InstanceType<typeof StateMixin>> ).extend( {
	mixins: [ StateMixin ],
	name: 'ErrorAmbiguousStatement',
	components: {
		IconMessageBox,
		BailoutActions,
	},
	computed: {
		propertyLabel(): HTMLElement {
			return new TermLabel( {
				propsData: {
					term: this.rootModule.getters.targetLabel,
					inLanguage: this.$inLanguage,
				},
			} ).$mount().$el as HTMLElement;
		},
		pageTitle(): string {
			return this.rootModule.state.pageTitle;
		},
		originalHref(): string {
			return this.rootModule.state.originalHref;
		},
	},
} );
</script>

<style lang="scss">
.wb-db-ambiguous-statement {
	@include errorBailout();
}
</style>
