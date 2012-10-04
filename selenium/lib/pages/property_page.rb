# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for property page

require 'ruby_selenium'

class PropertyPage < EntityPage
  include PageObject

  div(:datatype, :class => "wb-datatype")

  # ***** METHODS *****
  # item url navigation
  def navigate_to_property
    navigate_to @@property_url
  end

  def get_property_id
    @@property_id
  end

end
