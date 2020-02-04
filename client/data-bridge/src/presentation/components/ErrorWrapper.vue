<template>
	<section class="wb-db-error">
		<ErrorPermission
			v-if="permissionErrors.length"
			:permission-errors="permissionErrors"
		/>
		<ErrorUnsupportedDatatype
			v-else-if="unsupportedDatatypeError !== null"
			:data-type="unsupportedDatatypeError.info.unsupportedDatatype"
		/>
		<ErrorDeprecatedStatement
			v-else-if="statementValueIsDeprecated"
		/>
		<ErrorUnknown v-else />
	</section>
</template>

<script lang="ts">
import Component, { mixins } from 'vue-class-component';
import { MissingPermissionsError, PageNotEditable } from '@/definitions/data-access/BridgePermissionsRepository';
import StateMixin from '@/presentation/StateMixin';
import ErrorPermission from '@/presentation/components/ErrorPermission.vue';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import ErrorUnsupportedDatatype from '@/presentation/components/ErrorUnsupportedDatatype.vue';
import ErrorDeprecatedStatement from '@/presentation/components/ErrorDeprecatedStatement.vue';
import ApplicationError, {
	ErrorTypes,
	UnsupportedDatatypeError,
} from '@/definitions/ApplicationError';

@Component( {
	components: {
		ErrorPermission,
		ErrorUnknown,
		ErrorUnsupportedDatatype,
		ErrorDeprecatedStatement,
	},
} )
export default class ErrorWrapper extends mixins( StateMixin ) {
	public get applicationErrors(): ApplicationError[] {
		return this.rootModule.state.applicationErrors;
	}

	public get permissionErrors(): MissingPermissionsError[] {
		return this.applicationErrors.filter( this.isPermissionError );
	}

	private isPermissionError( error: ApplicationError ): error is MissingPermissionsError {
		return ( Object.values( PageNotEditable ) as string[] ).includes( error.type );
	}

	public get unsupportedDatatypeError(): UnsupportedDatatypeError|null {
		for ( const applicationError of this.applicationErrors ) {
			if ( applicationError.type === ErrorTypes.UNSUPPORTED_DATATYPE ) {
				return applicationError;
			}
		}
		return null;
	}

	public get statementValueIsDeprecated(): boolean {
		return this.applicationErrors.some(
			( applicationError ) => applicationError.type === ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT,
		);
	}
}
</script>

<style lang="scss">
.wb-db-error {
	padding: $padding-panel-form;
}
</style>
