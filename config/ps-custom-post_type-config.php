<?php
/*
 * Description: Custom PostType Config
 * Author: Wangbin
*/

	/**************追加カスタム投稿タイプおよび追加するタクソノミー****************************/

	//追加カスタム投稿タイプ共通配列 
	//Taxonomy 名追加カテゴリ-、配列のように入力すれば、一つカスタム投稿タイプに複数のタクソノミーを構成できます。

	$taxonomy = new stdClass( );
	$taxonomy->sample = new stdClass( );

	$taxonomy->sample->category_name= array('sample-category' => 'カテゴリー(サンプル)');
	//追加記事タイプ
	$taxonomy->sample->post_type = 'sample';
	//追加タイプ
	//$taxonomy->sample->tax_type = array('sample_type' => 'タイプ(sample)');
	//表示ページ
	$taxonomy->sample->show_page = 'sample_show';
	//管理画面表示名
	$taxonomy->sample->show_name = 'サンプル';
	//表示英語名
	$taxonomy->sample->show_name_eng = 'Sample';
	//管理画面表示ユーザレベル
	$taxonomy->sample->user_level = 3;
	//管理画面表示ユーザGroup 検討中
	//$taxonomy->sample->user_capabilities = $user_cap->ShopManager;
	//投稿の本文非表示否
	$taxonomy->sample->editor_show = false;
	define('PSASMPLE', $taxonomy->sample->post_type);

	/**複数のカスタムの場合下記のような配列を追加してください。**/
	/*追加カスタム投稿タイプ共通配列 
	//Taxonomy 名追加カテゴリ-、配列のように入力すれば、一つカスタム投稿タイプに複数のタクソノミーを構成できます。
	$taxonomy->sample2->category_name= array('sample2-category' => 'カテゴリー(サンプル)2');
	//追加記事タイプ
	$taxonomy->sample2->post_type = 'sample2';
	//追加タイプ
	//$taxonomy->sample2->tax_type = array('sample2_type' => 'タイプ(sample2)');
	//表示ページ
	$taxonomy->sample2->show_page = 'sample2_show';
	//管理画面表示名
	$taxonomy->sample2->show_name = 'サンプル2';
	//表示英語名
	$taxonomy->sample2->show_name_eng = 'Sample2';
	//管理画面表示ユーザレベル
	$taxonomy->sample2->user_level = 3;
	//管理画面表示ユーザGroup 検討中
	//$taxonomy->sample2->user_capabilities = $user_cap->ShopManager;
	//投稿の本文非表示否
	$taxonomy->sample2->editor_show = false;
	define('PSASMPLE2', $taxonomy->sample2->post_type);
	*/

	/* * グルバル変数宣言 */
	global $all_post_type , $PsTaxonomyConf;
	foreach ( $taxonomy as $key => $MyConf  ){      
		$all_post_type[$MyConf->post_type] = $MyConf->post_type;
	}

	$PsTaxonomyConf = $taxonomy;
	

	/***************投稿タグの処理***********************/
	/********特別な処理がない場合、コメントアウトしてください。**************/
	$custom_post_tag = new stdClass( );
	$custom_post_tag->show_name = 'カスタムタグ';
	//表示英語名
	$custom_post_tag->show_name_eng = 'Custom tag';
	
	
	
	/***************Attachement のカスタムタクソノミー***********************/
	/********処理がない場合、コメントアウトしてください。**************/
	$attachement = new stdClass( );
	$attachement->taxonomy = 'media_genres';
	$attachement->show_name = 'カスタムメィデア分類';
	$attachement->show_name_en = 'Custom Media Genres';


?>
