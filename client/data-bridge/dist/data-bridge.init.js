function countLinks() {
    var dataBridgeConfig = mw.config.get('wbDataBridgeConfig'), linkRegexp = new RegExp(dataBridgeConfig.hrefRegExp), validLinks = Array.from(document.querySelectorAll('a[href]'))
        .filter(function (element) {
        return !!element.href.match(linkRegexp);
    });
    // eslint-disable-next-line no-console
    console.log("Number of links potentially usable for data bridge: " + validLinks.length);
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', countLinks);
}
else {
    countLinks();
}
