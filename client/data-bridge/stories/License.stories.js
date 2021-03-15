import License from '@/presentation/components/License';

export default {
	title: 'License',
	component: License,
};

export function normal() {
	return {
		components: { License },
		template:
			`<License
			/>`,
	};
}
