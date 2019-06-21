if (document.readyState === 'complete') {
    countLinks();
}
else {
    document.addEventListener('DOMContentLoaded', countLinks);
}
function countLinks() {
    var linkRegexp = /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/;
    var validLinks = Array.from(document.querySelectorAll('a[href]'))
        .filter(function (element) {
        return element.href.match(linkRegexp);
    });
    // eslint-disable-next-line no-console
    console.log('Number of links potentially usable for wikidata bridge: ' + validLinks.length);
}
