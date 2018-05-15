(function () {
	'use strict';
	const questionIcon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNiAyNiI+PHBhdGggZD0iTTEzIDBBMTMgMTMgMCAwIDAgMCAxM2ExMyAxMyAwIDAgMCAxMyAxMyAxMyAxMyAwIDAgMCAxMy0xM0ExMyAxMyAwIDAgMCAxMyAwem0uNSA1LjFjMS43IDAgMyAuNCA0IDEuMSAxIC44IDEuNiAxLjggMS42IDMgMCAuNy0uMSAxLjMtLjMgMS45LS4yLjUtLjUgMS0uOSAxLjRsLTEuMyAxLTEuOC45djIuM0gxMXYtMy41bDEuNC0uNGE1IDUgMCAwIDAgMS4yLS42bDEtMWMuMi0uNC4zLS44LjMtMS4zIDAtLjYtLjItMS4xLS42LTEuNC0uNS0uMy0xLjEtLjUtMi0uNWwtMS42LjNjLS42LjMtMS4yLjUtMS43LjloLS40VjZsMi0uNiAyLjgtLjN6bS0yLjYgMTNIMTVWMjFoLTQuMlYxOHoiLz48L3N2Zz4=';

	const starIcon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNC43IDIzLjUiPjxwYXRoIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwMCIgZD0iTTEyLjMgMS42bDIuNiA4IDguMi0uMS02LjcgNC44IDIuNiA3LjktNi43LTUtNi42IDUgMi42LTcuOS02LjctNC44aDguMnoiLz48L3N2Zz4=';

	const starCheckedIcon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNC43IDIzLjUiPjxwYXRoIGZpbGw9IiNmZmQ5NjYiIHN0cm9rZT0iIzAwMCIgZD0iTTEyLjMgMS42bDIuNiA4IDguMi0uMS02LjcgNC44IDIuNiA3LjktNi43LTUtNi42IDUgMi42LTcuOS02LjctNC44aDguMnoiLz48L3N2Zz4=';

	const eyeIcon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNS45IDEyLjkiPjxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKC05NiAtMTQ5NC40KSI+PHBhdGggZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMDAwIiBkPSJNOTYuNyAxNTAxYzYuNi04LjMgMTcuOC04IDI0LjYgMC05LjQgNy44LTE1LjUgNy42LTI0LjYgMHoiLz48ZWxsaXBzZSBjeD0iMTA5IiBjeT0iMTQ5OS4yIiByeD0iNC4zIiByeT0iNC4zIi8+PHBhdGggZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9Ii43IiBkPSJNMTA5LjYgMTQ5Ni41YzEuMSAwIDIuMiAxIDIuMiAyIi8+PC9nPjwvc3ZnPg==';

	const template = `
		<section class="termbox">
			<header class="header">
				<h1 class="label">{{ current.label }}</h1>
				<p class="description">{{ current.description }}</p>
				<p class="aliases">{{ current.aliases }}</p>
				<p class="teaser">in {{ hasLanguages }} languages - {{ hasUserLanguages }} of your languages</p>
			</header>
			<table>
				<thead><tr>
					<td><img src="${questionIcon}" alt="help" style="height: 1em"></td>
					<td>Language</td>
					<td>Label</td>
					<td>Description</td>
					<td>Also known as</td>
				</tr></thead>
				<tfoot>
					<tr><td colspan="5">some action</td></tr>
				</tfoot>
				<tbody>
					<termbox-row v-for="item in items" :key="item.languageCode" :item="item" :favouriteLanguages="favouriteLanguages" :currentLanguage="currentLanguage"/>
				</tbody>
				
			</table>
			
		</section>
	`;

	const termboxRowTemplate = `
	<tr>
		<td class="info">
			<img src="${eyeIcon}" alt="Current" v-if="isCurrent">
			<img src="${starCheckedIcon}" alt="Favourite" v-else-if="isFavourite">
			<img src="${starIcon}" alt="Just an empty start" v-else>
		</td>
		<td class="language">{{item.languageCode}}</td>
		<td class="label">{{item.label}}</td>
		<td class="description">{{item.description}}</td>
		<td class="aliases">{{item.aliases}}</td>
	</tr>
	`;


	const TermBoxRow = {
		props: ['item', 'currentLanguage', 'favouriteLanguages'],
		template: termboxRowTemplate,
		computed: {
			isCurrent: function () {
				return this.currentLanguage === this.item.languageCode;
			},
			isFavourite: function () {
				return this.favouriteLanguages.indexOf(this.item.languageCode) >= 0;
			},
		}
	};

	var app = new Vue( {
		el: '#mobile-termbox',
		template: template,
		components: {
			'termbox-row': TermBoxRow
		},
		data: 	() => ({
			currentLanguage: 'en',
			favouriteLanguages: ['de'],
			items: [
				{
					languageCode: 'en',
					label: 'Berlin',
					description: 'Capital City of Germany',
					aliases: 'Berlin, Germany',
				},
				{
					languageCode: 'de',
					label: 'Das Berlin',
					description: 'Das Capital City of Germany',
					aliases: 'Berlin, Deutchland',
				},
				{
					languageCode: 'ru',
					label: 'Берлин',
					description: 'Столица Германии',
					aliases: 'Берлин, Германия',
				},
			],
		}),
		computed: {
			current: function () {
				return this.items.filter( ( i ) => i.languageCode === this.currentLanguage )[ 0 ];
			},
			hasLanguages: function () {
				return this.items.length;
			},
			hasUserLanguages: function () {
				//FIXME Wrong implementation
				return this.items.length;
			}
		}
	} );
})();
