class AddItemPage
  include PageObject

  # TODO: make API call to add a new item
  # page_url 'http://localhost/mediawiki/api.php'
  
  def create_new_item(item_name)
    postData = Net::HTTP.post_form(URI.parse('http://localhost/mediawiki/api.php'), 
                                     {'format'=>'json',
                                      'action'=>'wbsetitem',
                                      'data'=>'{}'})
    result = JSON.parse(postData.body)
    item_id = result['item']['id']
    # puts result
    
    postData = Net::HTTP.post_form(URI.parse('http://localhost/mediawiki/api.php'), 
                                         {'format'=>'json',
                                          'action'=>'wbsetlanguageattribute',
                                          'id'=>item_id,
                                          'language'=>'en',
                                          'label'=>item_name,
                                          'item'=>'set'})
    result = JSON.parse(postData.body)
    # puts result      
    puts result['success']
      
    return item_id
  end
end
