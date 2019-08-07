import { MutationTree } from 'vuex';
import StatementsState from '@/store/entity/statements/StatementsState';
import { statementMutations } from '@/store/entity/statements/statementMutations';
import { mainSnakMutationTypes } from '@/store/entity/statements/mainSnakMutationTypes';
import resolveMainSnak from '@/store/entity/statements/resolveMainSnak';
import buildMainSnakMutations from '@/store/entity/statements/snaks/mutations';
import MainSnakPath from '@/store/entity/statements/MainSnakPath';

const mainSnakMutations = buildMainSnakMutations<MainSnakPath>(
	mainSnakMutationTypes, resolveMainSnak,
);

export const mutations: MutationTree<StatementsState> = {
	...statementMutations,
	...mainSnakMutations,
};
