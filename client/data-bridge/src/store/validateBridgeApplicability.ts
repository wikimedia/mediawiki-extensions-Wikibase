import { ActionContext } from 'vuex';
import Application from '@/store/Application';
import { NS_ENTITY, NS_STATEMENTS } from '@/store/namespaces';
import { STATEMENTS_IS_AMBIGUOUS } from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import MainSnakPath from '@/store/entity/statements/MainSnakPath';
import { getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import { ErrorTypes } from '@/definitions/ApplicationError';
import { BRIDGE_ERROR_ADD } from '@/store/actionTypes';

export default function validateBridgeApplicability(
	context: ActionContext<Application, Application>,
	path: MainSnakPath,
): void {
	if (
		context.getters[
			getter( NS_ENTITY, NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS )
		]( path.entityId, path.propertyId ) === true
	) {
		context.dispatch( BRIDGE_ERROR_ADD, [ { type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT } ] );
	}

	if (
		context.getters[
			getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
		]( path ) !== 'value'
	) {
		context.dispatch( BRIDGE_ERROR_ADD, [ { type: ErrorTypes.UNSUPPORTED_SNAK_TYPE } ] );
	}

	if (
		context.getters[
			getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
		]( path ) !== 'string'
	) {
		context.dispatch( BRIDGE_ERROR_ADD, [ { type: ErrorTypes.UNSUPPORTED_DATAVALUE_TYPE } ] );
	}
}
