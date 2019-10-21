import { ActionContext } from 'vuex';
import Application from '@/store/Application';
import { NS_ENTITY, NS_STATEMENTS } from '@/store/namespaces';
import { STATEMENTS_IS_AMBIGUOUS } from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import MainSnakPath from '@/store/entity/statements/MainSnakPath';
import { getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';

export default function validateBridgeApplicability(
	context: ActionContext<Application, Application>,
	path: MainSnakPath,
): boolean {
	if (
		context.getters[
			getter( NS_ENTITY, NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS )
		]( path.entityId, path.propertyId ) === true
	) {
		return false;
	}

	if (
		context.getters[
			getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
		]( path ) !== 'value'
	) {
		return false;
	}

	if (
		context.getters[
			getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
		]( path ) !== 'string'
	) {
		return false;
	}

	return true;
}
