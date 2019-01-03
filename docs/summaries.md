= Summaries =

Wikibase uses “magic” edit summaries that can later be parsed and localized for display. This is done via the Format-Autocomment hook, handled by Summary::onFormat.

== Magic summaries ==

Below are examples of the magic summaries currently used.

''NOTE'': The block between the /* and the */ (the “auto-comment”) specify a system message and the parameters for that message (the “comment arguments”). By convention, the first comment argument is a count, used to trigger the plural version of the message when needed. The second argument, if present, is a language code or a site code.

; wbsetsitelink
:* <code>/* wbsetsitelink-add:1|site */</code> linktitle
:* <code>/* wbsetsitelink-set:1|site */</code> linktitle
:* <code>/* wbsetsitelink-remove:1|site */</code>
; wbsetlabel
:* <code>/* wbsetlabel-add:1|lang */</code> value
:* <code>/* wbsetlabel-set:1|lang */</code> value
:* <code>/* wbsetlabel-remove:1|lang */</code> value
; wbsetdescription
:* <code>/* wbsetdescription-add:1|lang */</code> value
:* <code>/* wbsetdescription-set:1|lang */</code> value
:* <code>/* wbsetdescription-remove:1|lang */</code> value
; wbeditentiry
:* <code>/* wbeditentity-update: */</code>
:* <code>/* wbeditentity-override: */</code>
:* <code>/* wbeditentity-create: */</code>
; wbsetalias
:* <code>/* wbsetaliases-set:1|lang */</code> values...
:* <code>/* wbsetaliases-remove:1|lang */</code> values...
:* <code>/* wbsetaliases-add:1|lang */</code> values...
; wblinktitles
:* <code>/* wblinktitles-create:2| */</code> fromSite:fromPage, toSite:toPage
:* <code>/* wblinktitles-connect:2| */</code> fromSite:fromPage, toSite:toPage
; wbsetclaimvalue
:* <code>/* wbsetclaimvalue:1 */</code> p123
:* <code>/* wbremoveclaims:n */</code> props...
:* <code>/* wbremoveclaims-remove:1 */</code> props...
; wbcreateclaim
:* <code>/* wbcreateclaim-value: */</code> p123
:* <code>/* wbcreateclaim-novalue: */</code> p123
:* <code>/* wbcreateclaim-somevalue: */</code> p123
; wbsetclaim
:* <code>/* wbsetclaim-update: */</code> p123
:* <code>/* wbsetclaim-create: */</code> p123
:* <code>/* wbsetclaim-update-qualifiers: */</code> p123 (claim)|p4 (qualifier)
:* <code>/* wbsetclaim-update-references: */</code> p123 (claim)|p4 (reference)
:* <code>/* wbsetclaim-update-rank: */</code> p123

The following summaries are not yet fully implemented and just included for reference:
; wbsetclaim
:* <code>/* wbsetclaim:1 */</code> p123
; wbremovereferences
:* <code>/* wbremovereferences:n */</code> p123
; wbsetreference
:* <code>/* wbsetreference:1 */</code> p123
; wbremovequalifiers
:* <code>/* wbremovequalifiers:n */</code> p123
; wbsetqualifiers
:* <code>/* wbsetqualifier:1 */</code> p123(claim)|p567(qualifier)
; wbsetstatementrank
:* <code>/* wbsetstatementrank-deprecated:1 */</code> p123
:* <code>/* wbsetstatementrank-normal:1 */</code> p123
:* <code>/* wbsetstatementrank-preferred:1 */</code> p123
