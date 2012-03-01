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
	//设置小图片所在的目录
	$autoSprite->imgPath = $path . '/img/';
	//合并后的大图存放的位置
	$autoSprite->outputImgFile = $path . '/output_img/a.png';
	//合并方向，1为垂直方向，2为水平方向，0为混合方向
	$autoSprite->direction = 1;
	//合并大图中小图之间的间距
	$autoSprite->margin = 10;
	//对应CSS所在的位置
	$autoSprite->cssPath = $path . '/css/';
	//自动替换后CSS存放的位置，如果覆盖原CSS文件的化，这里的值和cssPath相同即可
	$autoSprite->cssSavePath = $path . '/css_save/';
	//css中图片的命中策略
	$autoSprite->cssImgFilter = 'cssImgFilter';
	//CSS中小图片地址替换为大图片时目录前缀
	$autoSprite->cssReplaceImgPrefixPath = '../img/first/';
	$output = $autoSprite->generate();

	?>
```

使用方式可以参考tests下的例子。

## 系统要求
1、PHP Version >= 5.2

2、需要支持dl函数

3、需要开启gd库


