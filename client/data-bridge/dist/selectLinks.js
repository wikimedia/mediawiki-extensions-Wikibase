module.exports = function selectLinks() {
    var linkRegexp = /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/;
    var validLinks = Array.from(document.querySelectorAll('a[href]'))
        .filter(function (element) {
        return !!element.href.match(linkRegexp);
    });
    return validLinks;
};
