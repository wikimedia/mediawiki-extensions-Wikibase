import License from '@/presentation/components/License';
import useStore from './useStore';

export default {
	title: 'License',
	component: License,
	decorators: [
		useStore( {
			config: { usePublish: true },
		} ),
	],
};

export function normal() {
	return {
		components: { License },
		template:
			`<License
			/>`,
	};
}
