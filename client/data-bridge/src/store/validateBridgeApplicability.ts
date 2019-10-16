import { ActionContext } from 'vuex';
import Application from '@/store/Application';
import { NS_ENTITY, NS_STATEMENTS } from '@/store/namespaces';
import { ENTITY_ID } from '@/store/entity/getterTypes';
import { STATEMENTS_IS_AMBIGUOUS } from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import { getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';

export default function validateBridgeApplicability(
	context: ActionContext<Application, Application>,
): boolean {
	const entityId = context.getters[ getter( NS_ENTITY, ENTITY_ID ) ];
	const path = {
		entityId,
		propertyId: context.state.targetProperty,
		index: 0,
	};

	if (
		context.getters[
			getter( NS_ENTITY, NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS )
		]( entityId, context.state.targetProperty ) === true
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
