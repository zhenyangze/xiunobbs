<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

$user = user_read($uid);
user_login_check($user);

// 账户（密码、头像），主题
if(empty($action) || $action == 'create') {
	
	$width = param('width', 0);
	$height = param('height', 0);
	$name = param('name');
	$data = param('data', '', FALSE);
	
	empty($data) AND message(-1, '数据为空');
	$data = base64_decode_image_data($data);
	$size = strlen($data);
	$size > 2048000 AND message(-1, '文件尺寸太大，不能超过 2M，当前大小：'.$size);
	
	$ext = file_ext($name, 7);
	$filetypes = include './conf/attach.conf.php';
	!in_array($ext, $filetypes['all']) AND $ext = '_'.$ext;
	
	$tmpanme = $uid.'_'.xn_rand(15).'.'.$ext; // 凑够 32 个字节，对齐。
	$tmpfile = $conf['upload_path'].'tmp/'.$tmpanme;
	$tmpurl = $conf['upload_url'].'tmp/'.$tmpanme;
	
	file_put_contents($tmpfile, $data) OR message(-1, '写入文件失败');
	
	// 保存到 session，发帖成功以后，关联到帖子。
	$filetype = attach_type($name, $filetypes);
	empty($_SESSION['tmp_files']) AND $_SESSION['tmp_files'] = array();
	$n = count($_SESSION['tmp_files']);
	$attach = array(
		'url'=>$tmpurl, 
		'path'=>$tmpfile, 
		'orgfilename'=>$name, 
		'filetype'=>$filetype, 
		'filesize'=>filesize($tmpfile), 
		'width'=>$width, 
		'height'=>$height, 
		'isimage'=>0, 
		'aid'=>'_'.$n
	);
	$_SESSION['tmp_files'][$n] = $attach;
	
	unset($attach['path']);
	message(0, $attach);

// 删除附件
} elseif($action == 'delete') {
	
	$aid = param(2);
	if(substr($aid, 0, 1) == '_') {
		$key = intval(substr($aid, 1));
		$tmp_files = _SESSION('tmp_files');
		!isset($tmp_files[$key]) AND message(-1,"$key 不存在");
		$attach = $tmp_files[$key];
		!is_file($attach['path']) AND message(-1, '文件不存在');
		unlink($attach['path']);
		unset($_SESSION['tmp_files'][$key]);
	} else {
		$aid = intval($aid);
		$r = attach_delete($aid);
		$r ===  FALSE AND message(-1, '删除错误');
	}
	
	message(0, '删除成功');
	
}

?>