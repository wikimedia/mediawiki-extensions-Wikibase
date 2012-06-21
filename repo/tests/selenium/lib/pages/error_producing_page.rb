# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for an error page

require 'ruby_selenium'

class ErrorProducingPage < NewItemPage
  include PageObject
  
  div(:wbErrorDiv, :class => "wb-tooltip-error-top-message")
  link(:wbErrorDetailsLink, :class => "wb-tooltip-error-details-link")
  
end
