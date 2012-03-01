<?php
/**
 * 
 * 图片混合模式合并
 * @author welefen
 * @version 1.0
 * @date 2011-11-02
 *
 */
class RectanglePacking {
	
	public static $fileList = array (); //图片文件列表
	

	public $margin = 0; //合并图片之间的间距
	

	public $nums = 5; //运行的次数
	

	public $pix = 16; //每次变化的像素值
	

	private $_firstFile = ''; //第一个文件
	

	private $_corners = array (); //存放可以放图片的点
	

	private $_firstCorners = array (); //第一个图片产生的corner
	

	private $_fileRects = array (); //保存图片矩形的信息
	

	private $_firstFileRects = array (); //第一个图片的信息
	

	private $_areas = array (); //保存每个合并方案的信息
	

	private $_singleMaxHeight = 0; //单个图片的最大高度
	

	private $_singleMaxWidth = 0; //单个图片的最大宽度
	

	private $_totalWidth = 0; //总宽度
	

	private $_maxWidth = 0; //最大的图片宽
	

	private $_maxHeight = 0; //最大的图片高
	

	private $_minRectArea = 0; //图片的最小面积
	

	public function __construct($fileList = array()) {
		$this->sortFiles ( $fileList );
		$this->_maxWidth = $this->_totalWidth;
		$this->_maxHeight = $this->_singleMaxHeight;
	}
	public function run() {
		$i = 0;
		$this->_firstPutIt ( $this->_firstFile );
		while ( $i ++ < $this->nums ) {
			$this->reset ( $i === 2 );
			$result = $this->genereateRect ();
			if (! $result) {
				continue;
			}
			$rectAreaInfo = $this->getRectArea ();
			$area = $rectAreaInfo ['area'];
			if ($this->_minRectArea === 0 || $area < $this->_minRectArea) {
				$this->_minRectArea = $area;
				$this->_areas [] = $this->_fileRects;
			}
		}
		return $this->_areas [count ( $this->_areas ) - 1];
	}
	/**
	 * 
	 * 
	 * @param boolean $first
	 */
	public function reset($first = false) {
		$this->_fileRects = $this->_firstFileRects;
		$this->_corners = $this->_firstCorners;
		$pix = $this->pix + $this->margin;
		$this->_maxWidth -= $pix;
		if ($first) {
			$this->_maxHeight += $pix;
		}
		if ($this->_maxWidth < $this->_singleMaxWidth) {
			$this->_maxWidth = $this->_totalWidth;
			$this->_maxHeight += $pix;
		}
	}
	/**
	 * 
	 * 生成单个图片区域
	 */
	public function genereateRect() {
		$i = 0;
		foreach ( self::$fileList as $file => $value ) {
			if ($i ++ == 0)
				continue;
			$result = $this->putIt ( $file );
			if ($result === false) {
				return false;
			}
		}
		return true;
	}
	/**
	 * 
	 * 将图片按高度从大到小排列
	 * @param array $fileList
	 */
	public function sortFiles($fileList) {
		uasort ( $fileList, create_function ( '$a,$b', '$sa=RectanglePacking::getFileRect($a);$sb=RectanglePacking::getFileRect($b);;return $sa["height"]<$sb["height"] ? 1 : -1;' ) );
		$result = array ();
		foreach ( $fileList as $file ) {
			$info = self::$fileList [$file];
			$result [$file] = $info;
			if ($this->_singleMaxHeight === 0) {
				$this->_singleMaxHeight = $info ['height'] + $this->margin; //第一个图片的高度是最大的
				$this->_firstFile = $file;
			}
			$this->_singleMaxWidth = max ( $this->_singleMaxWidth, $info ['width'] );
			$this->_totalWidth += $info ['width'] + $this->margin;
		}
		self::$fileList = $result;
	}
	/**
	 * 
	 * 获取图片的矩形信息
	 * @param string $file
	 */
	public static function getFileRect($file) {
		if (! array_key_exists ( $file, self::$fileList )) {
			list ( $width, $height ) = getimagesize ( $file );
			$array = array ('width' => $width, 'height' => $height );
			self::$fileList [$file] = $array;
			return $array;
		}
		return self::$fileList [$file];
	}
	/**
	 * 
	 * 设置小图片所在的位置信息
	 * @param string $file
	 * @param int $x
	 * @param int $y
	 */
	public function setStartPos($file, $x, $y) {
		$this->_fileRects [$file] = array_merge ( self::getFileRect ( $file ), array ('x' => $x, 'y' => $y ) );
	}
	/**
	 * 
	 * 首个图片的设置和生成corners
	 * @param string $file
	 */
	private function _firstPutIt($file) {
		$rectInfo = self::getFileRect ( $file );
		if ($rectInfo ['height'] <= $this->_maxHeight) {
			$this->_corners [] = $this->getCorner ( 0, $rectInfo ['height'] );
		}
		if ($rectInfo ['width'] < $this->_maxWidth) {
			$this->_corners [] = $this->getCorner ( $rectInfo ['width'], 0 );
		}
		$this->setStartPos ( $file, 0, 0 );
		$this->_firstCorners = $this->_corners;
		$this->_firstFileRects = $this->_fileRects;
	}
	public function putIt($file) {
		$rectInfo = self::getFileRect ( $file );
		for($i = 0; $i < count ( $this->_corners ); $i ++) {
			$corner = $this->_corners [$i];
			$x = $corner ['x'];
			$y = $corner ['y'];
			if ($x + $rectInfo ['width'] <= $this->_maxWidth && $y + $rectInfo ['height'] <= $this->_maxHeight) {
				if ($this->contains ( $x, $y, $rectInfo ['width'], $rectInfo ['height'] )) {
					continue;
				}
				if ($y + $rectInfo ['height'] < $this->_maxHeight) {
					$this->_corners [] = $this->getCorner ( $x, $y + $rectInfo ['height'] );
				}
				if ($x + $rectInfo ['width'] < $this->_maxWidth) {
					$this->_corners [] = $this->getCorner ( $x + $rectInfo ['width'], $y );
				}
				array_splice ( $this->_corners, $i, 1 );
				$this->setStartPos ( $file, $x, $y );
				//$this->sortCorners ();
				return true;
			}
		}
		return false;
	}
	/**
	 * 
	 * 将已有的corners按x值从小到大排列
	 */
	public function sortCorners() {
		if (count ( $this->_corners ) <= 1)
			return true;
		uasort ( $this->_corners, create_function ( '$a,$b', 'return $a["x"] >= $b["x"] ? 1:-1;' ) );
	}
	/**
	 * 
	 * 获取corner
	 * @param int $x
	 * @param int $y
	 */
	public function getCorner($x, $y) {
		if ($x > 0)
			$x += $this->margin;
		if ($y > 0)
			$y += $this->margin;
		$result = array ('x' => $x, 'y' => $y/*, 'rect' => array ($x, $y, $this->_maxWidth, $this->_maxHeight ) */);
		return $result;
	}
	/**
	 * 
	 * 检测将一个小图片放在一个corner是否被其他图片包含
	 * 也就是小图片所需要的矩形区域是否已经被占据
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @return boolean
	 */
	public function contains($x, $y, $width, $height) {
		$targetTop = $y;
		$targetLeft = $x;
		$targetRight = $x + $width;
		$targetBottom = $y + $height;
		foreach ( $this->_fileRects as $file => $value ) {
			$sourceTop = $value ['y'];
			$sourceLeft = $value ['x'];
			$sourceRight = $value ['x'] + $value ['width'];
			$sourceBottom = $value ['y'] + $value ['height'];
			$maxTop = max ( $sourceTop, $targetTop );
			$minBottom = min ( $sourceBottom, $targetBottom );
			$maxLeft = max ( $sourceLeft, $targetLeft );
			$minRight = min ( $sourceRight, $targetRight );
			if ($minRight > $maxLeft && $minBottom > $maxTop) {
				return true;
			}
			$hw = ($targetLeft + $targetRight) / 2;
			$hh = ($targetBottom + $targetTop) / 2;
			if ($hw >= $sourceLeft && $hw <= $sourceRight && $hh >= $sourceTop && $hh <= $sourceBottom) {
				return true;
			}
			if ($sourceBottom === $targetBottom && $sourceLeft === $targetLeft && $sourceRight === $targetRight && $sourceTop === $targetTop) {
				return true;
			}
		}
		return false;
	}
	/**
	 * 
	 * 获取生成的大图片的矩形信息
	 * @param array $fileRects
	 */
	public function getRectArea($fileRects = array()) {
		$maxHeight = $maxWidth = 0;
		if (count ( $fileRects ) === 0) {
			$fileRects = $this->_fileRects;
		}
		foreach ( $fileRects as $file => $value ) {
			$maxHeight = max ( $maxHeight, $value ['y'] + $value ['height'] );
			$maxWidth = max ( $maxWidth, $value ['x'] + $value ['width'] );
		}
		return array ('width' => $maxWidth, 'height' => $maxHeight, 'area' => $maxHeight * $maxWidth );
	}
	/**
	 * 
	 * 获取生成的最后一个大图片的矩形信息
	 */
	public function getLastRectArea() {
		return $this->getRectArea ( $this->_areas [count ( $this->_areas ) - 1] );
	}
}

