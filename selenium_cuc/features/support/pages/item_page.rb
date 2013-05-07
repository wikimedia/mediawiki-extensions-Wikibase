# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for item page

#require 'ruby_selenium'

class ItemPage < EntityPage
  include PageObject

  # ***** METHODS *****
  # item url navigation
  def navigate_to_item
    navigate_to @@item_url
  end

  def navigate_to_item_en
    navigate_to @@item_url + "?uselang=en"
  end

  def navigate_to_item_de
    navigate_to @@item_url + "?uselang=de"
  end

  def get_item_id
    @@item_id
  end

  def get_item_url
    @@item_url
  end

end
