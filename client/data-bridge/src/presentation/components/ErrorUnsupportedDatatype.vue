<template>
	<div class="wb-db-unsupported-datatype">
		<IconMessageBox
			class="wb-db-unsupported-datatype__message"
			type="notice"
			:inline="true"
		>
			<p class="wb-db-unsupported-datatype__head">
				{{ $messages.get(
					$messages.KEYS.UNSUPPORTED_DATATYPE_ERROR_HEAD,
					targetProperty,
				) }}
			</p>
			<p class="wb-db-unsupported-datatype__body">
				{{ $messages.get(
					$messages.KEYS.UNSUPPORTED_DATATYPE_ERROR_BODY,
					targetProperty,
					dataType
				) }}
			</p>
		</IconMessageBox>
		<BailoutActions
			class="wb-db-unsupported-datatype__bailout"
			:original-href="originalHref"
			:page-title="pageTitle"
		/>
	</div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import Component, { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';
import DataType from '@/datamodel/DataType';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';

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
	public get targetProperty(): string {
		return this.rootModule.state.targetProperty;
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
	&__message {
		margin: 0 $margin-center-column-side;
	}

	&__head {
		font-weight: bold;
		line-height: px-to-em( 22px );
		margin-bottom: px-to-em( 8px );
	}

	&__body {
		line-height: px-to-em( 22px );
	}
}
</style>
