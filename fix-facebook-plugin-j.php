<?php
/*
Plugin Name: Fix Facebook Plugin J
Plugin URI: http://blog1.dd-company.com/wordpress-plugin-page/fix-facebook-plugin-j/
Description: "Fix Facebook Plugin J" is plugin for japanese language user, work well with WordPress plugin"Facebook" autogenerating Open Graph Protocol.
Author: D&D Company
Version: 0.0.1
Author URI: http://dd-company.com/
*/


/*
	Copyright (C) 2012  D&D Company

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


/**
*プラグイン Facebook for Wordpress の修正
*package Facebook version 1.0.2向け
*
*OGP metaタグの修正
*locale を en_US から ja_JP
*(fb-core.php 233行目のフィルターフック fb_locale にフック)
*image の値は、キャッチ画像があればそれに、無くて、アップ画像があればそれを、無ければ、デフォルト画像を使用
*published_time, modified_time を、修正
*(fb-open-graph.php 161行目のフィルターフック fb_meta_tags にフック)
*/

//locale修正
add_filter('fb_locale','fbfixj_fix_locale');

function fbfixj_fix_locale($locale){
	return 'ja_JP';
}

//画像のプロパティ修正
add_filter('fb_meta_tags','fbfixj_fix_ogp',10,2);

function fbfixj_fix_ogp($meta_tags,$post){
	//locale修正
//	$meta_tags['http://ogp.me/ns#locale'] = 'ja_JP';
	$post_id = $post->ID;
	$image = fbfixj_img_ogp($post_id);
	//画像の設定値修正
	$meta_tags['http://ogp.me/ns#image'] = array( $image );
	//published_time, modified_time の修正
	$meta_tags['http://ogp.me/ns/article#published_time'] = fbfixj_time_ogp($meta_tags['http://ogp.me/ns/article#published_time']);
	$meta_tags['http://ogp.me/ns/article#modified_time'] = fbfixj_time_ogp($meta_tags['http://ogp.me/ns/article#modified_time']);
	
	return $meta_tags;
}

//published_time, modified_time の修正
function fbfixj_time_ogp($arg){
	$pattern =  '/\+00:00$/';
	$fix_time = preg_replace($pattern,'+0900',$arg);
	return $fix_time;
}

//画像情報取得
function fbfixj_img_ogp($id) {
	//デフォルト画像のURL指定
	//設定値読み込み
	if(!get_option('default_img')){
		$default_img = '';
	}
	else{
		$default_img=esc_url(get_option('default_img'));
	}
	
	//画像サイズの指定 ('thumbnail','medium',('large'or'full'))
//	$img_size = 'medium';
	//設定値読み込み
	if(!get_option('img_size')){
		$img_size = 'thumbnail';
	}
	else{
		$img_size=get_option('img_size');
	}
	
	//アイキャッチ画像が存在する時
	if(has_post_thumbnail($id) && (is_single() || is_page())){
		list($img_url, $img_width, $img_height) = wp_get_attachment_image_src( get_post_thumbnail_id(), $img_size);
	}
	
	//アイキャッチ画像が存在しない時
	else{
		$query = array(
			'post_parent'		=>	$id,
			'post_type'			=>	'attachment',
			'post_mime_type'	=>	'image'
		);
		
		//ポストの添付画像の情報取得
		$post_img = get_children($query);
//		var_dump($post_img);

		//ポストに添付画像がある時
		if(!empty($post_img) && (is_single() || is_page())){
			$keys = array_keys($post_img);
//			var_dump($keys);exit;
			//最初にアップロードされた画像IDを取得
			$img_id = array_pop($keys);
//			echo $img_id;exit;

			//画像IDからサムネイルの情報を取得
			list($img_url, $img_width, $img_height) = wp_get_attachment_image_src($img_id, $img_size);
//			var_dump($thumb);exit;
		}
		
		//ポストに添付画像が無い時
		else{
			$img_url = $default_img;
			list($img_width, $img_height) = getimagesize($img_url);
		}
	}
	
	if(isset($img_url)){
//		$image['url'] = preg_replace('/^\//', get_bloginfo('url') . '/', $img_url);
		$image['url'] = $img_url;
	}
	if(isset($img_width)){
		$image['width'] = $img_width;
	}
	if(isset($img_height)){
		$image['height'] = $img_height;
	}
//	var_dump($image);exit;
	return $image;
}


add_action('admin_menu', 'fbfixj_add_admin_page');

function fbfixj_add_admin_page() {
	add_options_page('fbfixj', 'Fix Facebook Plugin J', 8, __FILE__, 'fbfixj_admin_page');
}

function fbfixj_admin_page(){
?>
<div class="wrap">
<h2>Fix Facebook Plugin J</h2>

<?php
if(!is_plugin_active('facebook/facebook.php'))
echo '<div class="error fade"><p>プラグイン <a href="http://wordpress.org/extend/plugins/facebook">Facebook</a> をインストールして有効化して下さい。</p></div>'; 
?>

<p>
このプラグインは、Facebook for Wordpress のOGP出力を書き換えます。<br />
Facebook for Wordpress プラグインが有効でないと正常に機能しません。
</p>
<h3>このプラグインの機能</h3>
<ol>
<li>
&lt;meta property="http://ogp.me/ns#locale" content="xxx" /&gt; を書き換えます。<br />
&lt;meta property="http://ogp.me/ns#locale" content="ja_JP" /&gt; にします。
</li>
<li>
&lt;meta property="http://ogp.me/ns#image" content="xxx" /&gt; xxx を書き換えます。
<ol>
<li type="a">
個別記事ページ、固定ページの時<br />
アイキャッチ画像が存在すればアイキャッチ画像のURLに書き換え、無ければ、添付画像のURLに書き換えます。<br />
どちらも存在しないときは、このページで設定したデフォルト画像のURLに書き換えます。
</li>
<li type="a">
個別記事ページ、固定ページ以外の時<br />
このページで設定したデフォルト画像のURLに書き換えます。
</li>
</ol>
</li>
<li>
&lt;meta property="http://ogp.me/ns#image:width" content="xxx" /&gt;<br />
&lt;meta property="http://ogp.me/ns#image:height" content="xxx" /&gt; を書き換えます。<br />
2 で書き換えたURLの画像の、画像サイズに書き換えます。
</li>
<li>
&lt;meta property="http://ogp.me/ns/article#published_time" content="xxx" /&gt;<br />
&lt;meta property="http://ogp.me/ns/article#modified_time" content="xxx" /&gt; を書き換えます。<br />
+00:00 を +0900 に書き換えます。
</li>
</ol>
<h3>設定</h3>
<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>
<table class="optiontable form-table">
<tr valign="top">
<th scope="row">default image URL</th>
 
<!--INPUT文のNAME属性を前述の変数と合わせます。-->
<td>
<input type="text" name="default_img" value="<?php if(get_option('default_img')){echo get_option('default_img');}else{echo 'http://';} ?>" style="width:400px;" />
<div>デフォルト画像に指定する画像のURLを記入します。 (横幅200px以上の画像を使用して下さい。)</div>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('image size') ?></th>
 
<td>
<input type="radio" name="img_size"<?php if(get_option('img_size') === 'thumbnail') echo ' checked="checked"'; ?> value="thumbnail" /> <?php _e('thumbnail') ?><br />
<input type="radio" name="img_size"<?php if(get_option('img_size') === 'medium') echo ' checked="checked"'; ?> value="medium" /> <?php _e('medium') ?><br />
<input type="radio" name="img_size"<?php if(get_option('img_size') === 'large') echo ' checked="checked"'; ?> value="large" /> <?php _e('large') ?><br />
<div>貼付画像とアイキャッチ画像のサイズを選択します。 (横幅200px以上になるようにして下さい。)</div>
</td>
</tr>
</table>
 
<!--ここのhiddenも必ず入れてください。複数あるときは、page_optionsは半角カンマで区切って記述。a,b,c　など-->
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="default_img,img_size" />
<p class="submit">
 
<!--SUBMITは英語で表記。_eで翻訳されるんです。便利！-->
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>
<?php
}
?>