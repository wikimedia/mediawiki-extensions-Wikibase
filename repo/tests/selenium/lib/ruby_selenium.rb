require 'net/http'
require 'uri'
require 'json'

WIKI_URL = "http://localhost/mediawiki/"
WIKI_USELANG = "en"
WIKI_SKIN = "vector" # "vector" "monobook"
WIKI_API_URL = WIKI_URL + 'api.php'
WIKI_USERNAME = "tobijat"
WIKI_PASSWORD = "darthvader"

class RubySelenium
  # do API request to create a new item
  def self.create_new_item
    if @item_id
      return @item_id
    end

    if !@item_token
      login_to_wiki
    end

    uri = URI.parse(WIKI_API_URL)
    req = Net::HTTP::Post.new(uri.path)
    
    # puts "cookie"
    # puts @session_cookie
    # puts "token"
    # puts @item_token
    
    req.set_form_data(
    {'format'=>'json',
      'action'=>'wbsetitem',
      'data'=>'{}',
      'token' => @item_token})
    req['Cookie'] = @session_cookie

    res = Net::HTTP.start(uri.hostname, uri.port) do |http|
      postData =  http.request(req)
      result = JSON.parse(postData.body)
      # puts result
      result['item'].should_not == nil
      result['item']['id'].should_not == nil
      @item_id = result['item']['id'].to_s()
    end
    return @item_id
  end

  def self.login_to_wiki
    uri = URI.parse(WIKI_API_URL)
    req = Net::HTTP::Post.new(uri.path)
    req.set_form_data(
    {'format'=>'json',
      'action'=>'login',
      'lgname'=>WIKI_USERNAME,
      'lgpassword'=>WIKI_PASSWORD})

    res = Net::HTTP.start(uri.hostname, uri.port) do |http|
      postData =  http.request(req)
      @session_cookie = postData.response['set-cookie']
      result = JSON.parse(postData.body)
      # puts result
      login_token = result['login']['token']

      req.set_form_data(
      {'format'=>'json',
        'action'=>'login',
        'lgname'=>WIKI_USERNAME,
        'lgpassword'=>WIKI_PASSWORD,
        'lgtoken'=>login_token})
      req['Cookie'] = @session_cookie

      postData =  http.request(req)
      result = JSON.parse(postData.body)
      # puts result
    end

    req = Net::HTTP::Post.new(uri.path)
    req.set_form_data(
    {'format'=>'json',
      'action'=>'wbsetitem',
      'gettoken'=>'1'})
    req['Cookie'] = @session_cookie
    res = Net::HTTP.start(uri.hostname, uri.port) do |http|
      postData =  http.request(req)

      result = JSON.parse(postData.body)
      # puts result
      @item_token = result['wbsetitem']['setitemtoken']
    end

    return true
  end

  # do API request to set a label for an item
  def self.set_item_label(item_label=generate_random_string(10), language=WIKI_USELANG)
    create_new_item

    uri = URI.parse(WIKI_API_URL)
    req = Net::HTTP::Post.new(uri.path)
    req.set_form_data(
    {'format'=>'json',
      'action'=>'wbsetlanguageattribute',
      'id'=>@item_id,
      'language'=>language,
      'label'=>item_label,
      'item'=>'set',
      'token' => @item_token})
    req['Cookie'] = @session_cookie

    res = Net::HTTP.start(uri.hostname, uri.port) do |http|
      http.request(req)
    end
  end

  # do API request to set a description for an item
  def self.set_item_description(item_description=generate_random_string(20), language=WIKI_USELANG)
    create_new_item

    uri = URI.parse(WIKI_API_URL)
    req = Net::HTTP::Post.new(uri.path)
    req.set_form_data(
    {'format'=>'json',
      'action'=>'wbsetlanguageattribute',
      'id'=>@item_id,
      'language'=>language,
      'description'=>item_description,
      'item'=>'set',
      'token' => @item_token})
    req['Cookie'] = @session_cookie

    res = Net::HTTP.start(uri.hostname, uri.port) do |http|
      http.request(req)
    end
  end

  # creates a new item and returns the URL for that item
  def self.get_new_item_url
    create_new_item
    item_url = WIKI_URL + "index.php/Data:q" + @item_id + "?uselang=" + WIKI_USELANG + "&useskin=" + WIKI_SKIN
    return item_url
  end

  def self.get_item_id
    return create_new_item
  end

  # creates a random string
  def self.generate_random_string(length=8)
    chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
    string = ''
    length.times { string << chars[rand(chars.size)] }
    return string
  end

end
