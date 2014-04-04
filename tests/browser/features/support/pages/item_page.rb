# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for item page

class ItemPage
  include PageObject
  include EntityPage

  # ***** METHODS *****
  # item url navigation
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

  def create_item_data(handles, empty = false)
    item_data = Hash.new
    handles.raw.each do |handle|
      handle = handle[0]
      label = empty ? '' : generate_random_string(8)
      description = empty ? '' : generate_random_string(20)
      data = '{"labels":{"en":{"language":"en","value":"' + label +
          '"}},"descriptions":{"en":{"language":"en","value":"' + description + '"}}}'
      item_data[handle] = data
    end

    item_data
  end

end
