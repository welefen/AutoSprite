<?php
$path = dirname(__FILE__);
$file =  '../src/AutoSprite.class.php';
function cssImgFilter($file){
	//return true;
}
require_once $file;
$autoSprite = new AutoSprite();
$autoSprite->imgPath = $path . '/img/dialog/';
$autoSprite->outputImgFile = $path . '/output_img/a.png';
$autoSprite->direction = 2;
$autoSprite->margin = 10;
$autoSprite->cssPath = $path . '/css/';
$autoSprite->cssSavePath = $path . '/css_save/';
$autoSprite->cssImgFilter = 'cssImgFilter';
$autoSprite->cssReplaceImgPrefixPath = '../img/output_img/';
$output = $autoSprite->run();


//获取小图片列表
$files = $autoSprite->imgFiles;
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>test</title>
<link href="css_save/a.css" type="text/css" rel="stylesheet"/>
<style type="text/css">
body{padding:5px;}
div .cc{color:red;}
div .aa{color: blue}
#a2 .cc{border:1px solid blue}
#a1 .aa{border:1px solid red}
</style>
</head>
<body>

<fieldset>
	<legend>原始图片</legend>
	<div  style="padding:20px;">
		<?php foreach ($files as $file):?>
			<?php $file = str_replace('/home/welefen/Documents/www/develop/AutoSprite', '', $file);?>
			<img src="http://www/develop/AutoSprite<?php echo $file?>" />
		<?php endforeach;?>
	</div>
</fieldset>
<fieldset style="margin-top:20px">
	<legend>合并图片</legend>
	<div style="padding:20px;">
		<img src="output_img/a.png" />
	</div>
</fieldset>


</body>
</html>
    
    
    
    
    
    