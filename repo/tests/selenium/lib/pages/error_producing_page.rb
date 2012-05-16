require 'ruby_selenium'

class ErrorProducingPage < ItemPage
  include PageObject
  
  div(:wbErrorDiv, :class => "wb-tooltip-error-top-message")
  link(:wbErrorDetailsLink, :class => "wb-tooltip-error-details-link")
  
end
