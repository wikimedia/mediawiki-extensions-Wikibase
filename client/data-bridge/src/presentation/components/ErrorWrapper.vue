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
		<ErrorAmbiguousStatement
			v-else-if="statementIsAmbiguous"
		/>
		<ErrorUnsupportedSnakType
			v-else-if="unsupportedSnakTypeError !== null"
			:snak-type="unsupportedSnakTypeError.info.snakType"
		/>
		<ErrorSaving
			v-else-if="isGenericSavingError"
		/>
		<ErrorSavingAssertUser
			v-else-if="isAssertUserFailedError"
			:login-url="loginUrl"
		/>
		<ErrorSavingEditConflict
			v-else-if="isEditConflictError"
			@reload="reload"
		/>
		<ErrorUnknown
			v-else
			@relaunch="relaunch"
		/>
	</section>
</template>

<script lang="ts">
import ErrorUnsupportedSnakType from '@/presentation/components/ErrorUnsupportedSnakType.vue';
import Component, { mixins } from 'vue-class-component';
import { MissingPermissionsError, PageNotEditable } from '@/definitions/data-access/BridgePermissionsRepository';
import StateMixin from '@/presentation/StateMixin';
import ErrorPermission from '@/presentation/components/ErrorPermission.vue';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import ErrorUnsupportedDatatype from '@/presentation/components/ErrorUnsupportedDatatype.vue';
import ErrorDeprecatedStatement from '@/presentation/components/ErrorDeprecatedStatement.vue';
import ErrorAmbiguousStatement from '@/presentation/components/ErrorAmbiguousStatement.vue';
import ErrorSaving from '@/presentation/components/ErrorSaving.vue';
import ErrorSavingAssertUser from '@/presentation/components/ErrorSavingAssertUser.vue';
import ErrorSavingEditConflict from '@/presentation/components/ErrorSavingEditConflict.vue';
import ApplicationError, {
	ErrorTypes,
	UnsupportedDatatypeError,
	UnsupportedSnakTypeError,
} from '@/definitions/ApplicationError';

@Component( {
	components: {
		ErrorUnsupportedSnakType,
		ErrorPermission,
		ErrorUnknown,
		ErrorUnsupportedDatatype,
		ErrorDeprecatedStatement,
		ErrorAmbiguousStatement,
		ErrorSaving,
		ErrorSavingAssertUser,
		ErrorSavingEditConflict,
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

	public get statementIsAmbiguous(): boolean {
		return this.applicationErrors.some(
			( applicationError ) => applicationError.type === ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT,
		);
	}

	public get unsupportedSnakTypeError(): UnsupportedSnakTypeError|null {
		for ( const applicationError of this.applicationErrors ) {
			if ( applicationError.type === ErrorTypes.UNSUPPORTED_SNAK_TYPE ) {
				return applicationError;
			}
		}
		return null;
	}

	public get isGenericSavingError(): boolean {
		return this.rootModule.getters.isGenericSavingError;
	}

	public get isAssertUserFailedError(): boolean {
		return this.rootModule.getters.isAssertUserFailedError;
	}

	public get isEditConflictError(): boolean {
		return this.rootModule.getters.isEditConflictError;
	}

	public get loginUrl(): string {
		return this.$clientRouter.getPageUrl(
			'Special:UserLogin',
			{
				warning: this.$messages.KEYS.LOGIN_WARNING,
			},
		);
	}

	private relaunch(): void {
		/**
		 * An event fired when it is time to relaunch the bridge (usually bubbled from a child component)
		 * @type {Event}
		 */
		this.$emit( 'relaunch' );
	}

	private reload(): void {
		/**
		 * An event fired when the user requested to reload the whole page (usually bubbled from a child component)
		 * @type {Event}
		 */
		this.$emit( 'reload' );
	}
}
</script>

<style lang="scss">
.wb-db-error {
	padding: $padding-panel-form;
}
</style>
