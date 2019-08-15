import { statementActions } from '@/store/entity/statements/statementActions';
import buildMainSnakActions from '@/store/entity/statements/snaks/actions';
import { mainSnakActionTypes } from '@/store/entity/statements/mainSnakActionTypes';
import { mainSnakMutationTypes } from '@/store/entity/statements/mainSnakMutationTypes';
import resolveMainSnak from '@/store/entity/statements/resolveMainSnak';
import MainSnakPath from '@/store/entity/statements/MainSnakPath';

const mainSnakActions = buildMainSnakActions<MainSnakPath>(
	mainSnakActionTypes,
	mainSnakMutationTypes,
	resolveMainSnak,
);

export const actions = {
	...statementActions,
	...mainSnakActions,
};
