require 'net/http'
require 'uri'
require 'json'

WIKI_URL = "http://localhost/mediawiki/"
WIKI_USELANG = "en"
WIKI_API_URL = WIKI_URL + 'api.php'

class RubySelenium
  # do API request to create a new item
  def self.create_new_item()
    if @item_id
      return @item_id
    end
    
    postData = Net::HTTP.post_form(URI.parse(WIKI_API_URL),
    {'format'=>'json',
      'action'=>'wbsetitem',
      'data'=>'{}'})
    result = JSON.parse(postData.body)
     @item_id = result['item']['id'].to_s()

    return @item_id
  end

  # do API request to set a label for an item
  def self.set_item_label(item_label=generate_random_string(10), language=WIKI_USELANG)
    create_new_item
    postData = Net::HTTP.post_form(URI.parse(WIKI_API_URL),
    {'format'=>'json',
      'action'=>'wbsetlanguageattribute',
      'id'=>@item_id,
      'language'=>language,
      'label'=>item_label,
      'item'=>'set'})
    result = JSON.parse(postData.body)
  end

  # do API request to set a description for an item
  def self.set_item_description(item_description=generate_random_string(20), language=WIKI_USELANG)
    create_new_item
    postData = Net::HTTP.post_form(URI.parse(WIKI_API_URL),
    {'format'=>'json',
      'action'=>'wbsetlanguageattribute',
      'id'=>@item_id,
      'language'=>language,
      'description'=>item_description,
      'item'=>'set'})
    result = JSON.parse(postData.body)
  end

  # creates a new item and returns the URL for that item
  def self.get_new_item_url
    create_new_item
    item_url = WIKI_URL + "index.php/Data:q" + @item_id + "?uselang=" + WIKI_USELANG
    return item_url
  end

  def self.get_item_id
    return create_new_item
  end

  # creates a random string
  def self.generate_random_string(length=8)
    chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ '
    string = ''
    length.times { string << chars[rand(chars.size)] }
    return string
  end

end
