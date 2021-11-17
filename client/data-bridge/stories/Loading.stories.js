import Loading from '@/presentation/components/Loading';
import loremIpsum from './loremIpsum';

export default {
	title: 'Loading',
	component: Loading,
};

const defaultArgTypes = {
	TIME_UNTIL_CONSIDERED_SLOW: {
		type: 'number',
		name: 'time until considered slow (ms)',
		defaultValue: 1000,
	},
	MINIMUM_TIME_OF_PROGRESS_ANIMATION: {
		type: 'number',
		name: 'minimum time of progress animation (ms)',
		defaultValue: 500,
	},
};

export const initializing = ( args, { argTypes } ) => {
	return {
		components: { Loading },
		props: Object.keys( argTypes ),
		template:
			'<Loading v-bind="$props">Content which may be slow</Loading>',
	};
};
initializing.args = {
	isInitializing: true,
	isSaving: false,
};
initializing.argTypes = {
	...defaultArgTypes,
	isInitializing: {
		type: 'boolean',
		name: 'initializing',
	},
	isSaving: { table: { disable: true } },
};

export const saving = ( args, { argTypes } ) => {
	return {
		components: { Loading },
		props: Object.keys( argTypes ),
		methods: {
			loremIpsum,
		},
		template:
			`<Loading v-bind="$props">
				<h3>I am under the loading bar</h3>
				<div style="max-width: 50em">
					{{ loremIpsum( 6, '-' ) }}
				</div>
			</Loading>`,
	};
};
saving.args = {
	isInitializing: false,
	isSaving: true,
};
saving.argTypes = {
	...defaultArgTypes,
	isSaving: {
		type: 'boolean',
		name: 'saving',
	},
	isInitializing: { table: { disable: true } },
};
