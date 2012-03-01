<?php
/**
 * 
 * 图片合成类
 * @author lichengyin
 *
 */
class AutoSprite_Generate {
	/**
	 * 
	 * 图片生成的engine
	 * @var string
	 */
	public $engine = '';
	/**
	 * 支持的图片生成方式列表
	 */
	private $_supportEngineList = array ('gd', 'imagick' );
	/**
	 * 具体的图片生成类实例
	 */
	private $_engineInstance = null;
	/**
	 * 默认配置项
	 */
	private $_defaultOptions = array (
		'filelist' => array (), 
		'margin' => 5, 
		'direction' => 1, 
		'background' => '', 
		'transparent' => true, 
		'output' => '', 
		'quality' => 75, 
		'colorsnum' => 256 
	);
	
	public function __construct($engine = '') {
		$this->checkSurpportEngine ();
		if ($engine && in_array ( $engine, $this->_supportEngineList )) {
			$this->engine = $engine;
		} else {
			$this->engine = $this->_supportEngineList [0];
		}
		$this->_engineInstance = AutoSprite::loadClass ( 'AutoSprite_Generate_' . ucfirst ( $this->engine ), true );
		$this->_engineInstance->generateInstance = $this;
	}
	/**
	 * 
	 * 检测支持哪些图片生成方式
	 */
	public function checkSurpportEngine() {
		$result = array ();
		foreach ( $this->_supportEngineList as $engine ) {
			if (extension_loaded ( $engine )) {
				$result [] = $engine;
			}
		}
		$this->_supportEngineList = $result;
	}
	/**
	 * 获取图片的后缀名
	 * @param string $file
	 */
	public function getImageExt($file) {
		$ext = strtoupper ( AutoSprite::getExtName ( $file ) );
		if ($ext === 'JPEG')
			return 'JPG';
		return $ext;
	}
	/**
	 * 创建目录
	 * @param string $dir
	 * @param int $mode
	 */
	public function mkdir($dir, $mode = 0777) {
		return AutoSprite::mkdir ( $dir, $mode );
	}
	/**
	 * 设置配置项
	 * @param array $options
	 */
	public function setOptions($options = array()) {
		$options = array_merge ( $this->_defaultOptions, $options );
		if (count ( $options ['filelist'] ) == 0)
			return false;
		$this->_engineInstance->setOptions ( $options );
		return true;
	}
	/**
	 * 生成合并后的图片，并返回小图片在大图片中所在的位置
	 * @param array $options
	 */
	public function generate($options = array()) {
		if (! $this->_engineInstance)
			return false;
		if (! $this->setOptions ( $options ))
			return false;
		$direction = $this->_engineInstance->options ['direction'];
		$result = array ();
		//图片混合拼接模式需要调用RectanglePacking类
		if ($direction === 0) {
			require_once dirname ( dirname ( __FILE__ ) ) . '/Vender/RectanglePacking.class.php';
			$rectPacking = new RectanglePacking ( $this->_engineInstance->options ['filelist'] );
			$rectPacking->margin = $this->_engineInstance->options ['margin'];
			$result = $rectPacking->run ();
			//获取输出大图片的相关信息
			$output_size = $rectPacking->getLastRectArea ();
		} else {
			$output_size = $this->calOutputSize ( $this->_engineInstance->options ['filelist'], $this->_engineInstance->options ['direction'], $this->_engineInstance->options ['margin'] );
		}
		//生成输出的文件
		$output_im = $this->_engineInstance->createOutputFile ( $output_size );
		//获取小图片所在大图片中的位置
		$css = $this->getOutputPos ( $output_im, $result );
		
		$output = $this->_engineInstance->options ['output'];
		//如果该文件已经存在，则删除
		if (file_exists ( $output )) {
			@unlink ( $output );
		}
		//创建输出文件的目录
		if (! $this->mkdir ( dirname ( $output ) )) {
			AutoSprite::throwException ( 'can not create output path ' . dirname ( $output ), 7 );
		}
		//检测是否能创建输出的文件
		file_put_contents ( $output, '' );
		if (file_exists ( $output )) {
			@unlink ( $output );
		} else {
			AutoSprite::throwException ( 'can not create output file' . $output, 8 );
		}
		//保存生成的大图片
		$this->_engineInstance->saveImage ( $output_im, $this->_engineInstance->options ['output'] );
		return $css;
	}
	/**
	 * 
	 * 获取小图片所在大图片中的位置
	 * @param image resource $output_im
	 * @param array $result
	 */
	public function getOutputPos($output_im, $result = array()) {
		$css = array ();
		if ($this->_engineInstance->options ['direction'] === 0) {
			foreach ( $result as $file => $value ) {
				$current_file_im = $this->_engineInstance->createImageFromFile ( $file );
				$this->_engineInstance->imagecopy ( $output_im, $current_file_im, $value ['x'], $value ['y'], array ($value ['width'], $value ['height'] ) );
				$css [$file] = array (0 - $value ['x'], 0 - $value ['y'], $value ['width'], $value ['height'] );
			}
			return $css;
		}
		$offset_x = $offset_y = 0;
		$direction = $this->_engineInstance->options ['direction'];
		foreach ( $this->_engineInstance->options ['filelist'] as $file ) {
			$current_file_im = $this->_engineInstance->createImageFromFile ( $file );
			$current_file_size = getimagesize ( $file );
			$this->_engineInstance->imagecopy ( $output_im, $current_file_im, $offset_x, $offset_y, $current_file_size );
			switch ($direction) {
				case 1 :
					$css [$file] = array (0 - $offset_x, 0 - $offset_y, $current_file_size [0], $current_file_size [1] );
					$offset_y += $current_file_size [1] + $this->_engineInstance->options ['margin'];
					;
					break;
				case 2 :
					$css [$file] = array (0 - $offset_x, 0 - $offset_y, $current_file_size [0], $current_file_size [1] );
					$offset_x += $current_file_size [0] + $this->_engineInstance->options ['margin'];
					;
					break;
			}
		}
		return $css;
	}
	/**
	 * 计算合并后的的图片宽和高
	 * @param array $file_list
	 */
	public function calOutputSize($file_list = array(), $direction = 0, $margin = 0) {
		$direction = intval ( $direction );
		$margin = intval ( $margin );
		$total_height = $max_height = $total_width = $max_width = 0;
		foreach ( $file_list as $file ) {
			if ($file) {
				$size = getimagesize ( $file );
				$total_width += $size [0] + $margin;
				if ($size [0] > $max_width)
					$max_width = $size [0];
				$total_height += $size [1] + $margin;
				if ($size [1] > $max_height)
					$max_height = $size [1];
			}
		}
		switch ($direction) {
			case 0 :
				break;
			case 1 :
				return array ('width' => $max_width, 'height' => $total_height - $margin );
				break;
			case 2 :
				return array ('width' => $total_width - $margin, 'height' => $max_height );
				break;
		}
		return array ('width' => 0, 'height' => 0 );
	}
	/**
	 * 格式化颜色
	 * @param string $color
	 */
	public function formatColor($color = '') {
		$color = strval ( $color );
		if (! $color)
			return '';
		$color = str_replace ( '#', '', $color );
		if (strlen ( $color ) == 3) {
			$colors = explode ( '', $color );
			$color = $colors [0] . $colors [0] . $colors [1] . $colors [1] . $colors [2] . $colors [2];
		}
		return $color;
	}
	/**
	 * 将颜色转化为RGB模式
	 * @param string $color
	 */
	public function getRGBColor($color) {
		$color = hexdec ( $color );
		$R = 0xFF & ($color >> 0x10);
		$G = 0xFF & ($color >> 0x8);
		$B = 0xFF & $color;
		return array ('R' => $R, 'G' => $G, 'B' => $B );
	}
	/**
	 * 格式化配置项
	 * @param array $options
	 */
	public function formatOptions($options) {
		$options ['margin'] = $this->unsignIntval ( $options ['margin'] );
		$options ['transparent'] = ! ! $options ['transparent'];
		$image_ext = $this->getImageExt ( $options ['output'] );
		if (! in_array ( $image_ext, array ('GIF', 'PNG' ) )) {
			$options ['transparent'] = false;
		}
		$options ['direction'] = $this->unsignIntval ( $options ['direction'] );
		$options ['quality'] = $this->unsignIntval ( $options ['direction'] );
		if ($options ['quality'] > 100) {
			$options ['quality'] = 100;
		}
		$options ['colorsnum'] = $this->unsignIntval ( $options ['colorsnum'] );
		return $options;
	}
	/**
	 * 
	 * 格式化整数值
	 * @param int $value
	 * @param int $default
	 */
	public function unsignIntval($value, $default = 0) {
		$value = intval ( $value );
		return $value >= 0 ? $value : $default;
	}
}