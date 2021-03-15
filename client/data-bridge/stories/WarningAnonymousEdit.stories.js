import WarningAnonymousEdit from '@/presentation/components/WarningAnonymousEdit';

export default {
	title: 'WarningAnonymousEdit',
	component: WarningAnonymousEdit,
};

export function normal() {
	return {
		components: { WarningAnonymousEdit },
		template: `<div style="max-width: 550px; max-height: 550px; border: 1px solid black;">
			<WarningAnonymousEdit login-url="https://example.com"/>
		</div>`,
	};
}
