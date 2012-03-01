## 简介

AutoSprite是一款将小图自动合并成大图片，并且可以自动修改对应CSS的css sprites工具。

相对之前的一些css sprites工具，具有以下的特点：

1、开发时在css直接写对应小图的url即可。如：background:url(小图片的地址)

2、后续如果有图片增删时，只用在对应的文件夹下增加或者删除对应的图片即可。

3、对于静态文件的缓存，开发时无需关心，工具自动进行。

## 使用方式

由于一个项目里可能会需要合并成多个css sprites, 所以需要知道将哪些小图片合并在一起，并且将这些小图片放在一个文件夹里。

```
	<?php

	$path = dirname(__FILE__);
	$file =  '../src/AutoSprite/AutoSprite.class.php';
	function cssImgFilter($file){
		//return true;
	}
	require_once $file;
	$autoSprite = new AutoSprite();
	$autoSprite->imgPath = $path . '/img/';
	$autoSprite->outputImgFile = $path . '/output_img/a.png';
	$autoSprite->direction = 1;
	$autoSprite->margin = 10;
	$autoSprite->cssPath = $path . '/css/';
	$autoSprite->cssSavePath = $path . '/css_save/';
	$autoSprite->cssImgFilter = 'cssImgFilter';
	$autoSprite->cssReplaceImgPrefixPath = '../img/first/';
	$output = $autoSprite->generate();

	?>
```

使用方式可以参考tests下的例子。

## 系统要求
1、PHP Version >= 5.2

2、需要支持dl函数

3、需要开启gd库


