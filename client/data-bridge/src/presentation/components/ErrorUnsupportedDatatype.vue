<template>
	<div class="wb-db-unsupported-datatype">
		<IconMessageBox
			class="wb-db-unsupported-datatype__message"
			type="notice"
			:inline="true"
		>
			<p
				class="wb-db-unsupported-datatype__head"
				v-html="$messages.get( $messages.KEYS.UNSUPPORTED_DATATYPE_ERROR_HEAD, propertyLabel )"
			/>
			<p
				class="wb-db-unsupported-datatype__body"
				v-html="$messages.get( $messages.KEYS.UNSUPPORTED_DATATYPE_ERROR_BODY, propertyLabel, dataType )"
			/>
		</IconMessageBox>
		<BailoutActions
			class="wb-db-unsupported-datatype__bailout"
			:original-href="originalHref"
			:page-title="pageTitle"
		/>
	</div>
</template>

<script lang="ts">
import { DataType } from '@wmde/wikibase-datamodel-types';
import { Prop } from 'vue-property-decorator';
import Component, { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import TermLabel from '@/presentation/components/TermLabel.vue';

/**
 * A component used to illustrate a datatype error which happened when
 * the user tried to edit a value which bridge does not support yet.
 */
@Component( {
	components: {
		IconMessageBox,
		BailoutActions,
	},
} )
export default class ErrorUnsupportedDatatype extends mixins( StateMixin ) {
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

	@Prop( { required: true } )
	public dataType!: DataType;
}
</script>

<style lang="scss">
.wb-db-unsupported-datatype {
	@include errorBailout();
}
</style>
