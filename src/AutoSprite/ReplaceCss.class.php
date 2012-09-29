<?php
/**
 * 
 * 自动替换CSS中的background-position
 * 目前是通过正则进行CSS替换，后续会将FL引进来
 * @author welefen
 *
 */
class AutoSprite_ReplaceCss {

	/**
	 * 
	 * CSS文件路径，用作保存的时候用
	 * @var string
	 */
	public $cssPath = '';

	/**
	 * 需要修改的CSS文件列表
	 * 
	 * @var array
	 */
	public $cssFiles = array ();

	/**
	 * 
	 * CSS保存路径，如果为空，则跟原始路径相同
	 * @var string
	 */
	public $cssSavePath = '';

	/**
	 * 
	 * css文件里图片的路径过滤函数
	 * @var function
	 */
	public $imgPathFilter = '';

	/**
	 * 
	 * 生成之后小图片对应的位置列表
	 * @var array
	 */
	public $imgFilesPattern = array ();

	/**
	 * 
	 * 需要把小图片替换掉的图片文件名,即生成的大图片的文件名（连带引用的路径）
	 * @var string
	 */
	public $replaceImg = '';

	/**
	 * 单个css selector的正则，不支持css里有expression
	 * 由于expression里可能含有{,}，会导致这里的正则失效
	 * 后续会结合FL里的CSS TOKEN分析来进行
	 */
	private $_cssSpritesPattern = '/([^\{\}\/]*)\{([^\{\}]*)\}/ies';

	/**
	 * 分析background-image里的url值的正则
	 */
	private $_urlPattern = '/url\s*\(\s*([\'\"]?)([^\'\"\)]+)\\1\s*\)/ies';

	/**
	 * 检测background-position是否有right, bottom的正则
	 */
	private $_backgroundPositionPattern = '/background(?:-position)?\s*:[^\;\{\}]*(right|bottom).*?/ies';

	/**
	 * background-image的正则
	 */
	private $_backgroundImagePattern = array (
		"/background(?:-image)?\s*:\s*;/ies", 
		"/background(?:-image)?\s*:\s*$/ies" 
	);

	/**
	 * 临时缓存
	 */
	private $_cache = array ();

	public function run() {
		if ($this->cssPath) {
			$this->cssPath = rtrim ( $this->cssPath ) . '/';
			$this->cssFiles += AutoSprite::getFiles ( $this->cssPath, 'css', $this->cssPath );
		}
		$this->cssFiles = array_unique ( $this->cssFiles );
		if (count ( $this->cssFiles ) === 0) {
			AutoSprite::throwException ( 'css files must be rather than 0 file', 12 );
		}
		foreach ( $this->cssFiles as $file ) {
			$this->replaceFile ( $file );
		}
	}

	/**
	 * 
	 * 替换单个CSS文件
	 * @param string $file
	 */
	public function replaceFile($file) {
		$this->_cache = array (
			'key' => array (), 
			'background' => '' 
		);
		$content = AutoSprite::getFileContent ( $file );
		$content = preg_replace ( $this->_cssSpritesPattern, "self::_replaceIt('\\1', '\\2', '" . $file . "')", $content );
		if (count ( $this->_cache ['key'] )) {
			$prefix = join ( ',', $this->_cache ['key'] );
			$prefix .= '{background-image:url("' . $this->_cache ['background'] . '");}' . "\n";
		}
		$content .= $prefix;
		$savePath = '';
		if ($this->cssSavePath) {
			$savePath = rtrim ( $this->cssSavePath, '/' );
		}
		if ($savePath) {
			if ($this->cssPath) {
				$nfile = str_replace ( $this->cssPath, '', $file );
				if ($nfile === $file) {
					AutoSprite::throwException ( 'css path error, please check!', 15 );
				} else {
					$file = $savePath . '/' . ltrim ( $nfile );
				}
			} else {
				$file = $savePath . '/' . AutoSprite::getFilename ( $file );
			}
			AutoSprite::mkdir ( dirname ( $file ) );
		}
		AutoSprite::setFileContent ( $file, $content );
	}

	/**
	 * 
	 * 替换单个CSS文件里面的内容
	 * @param string or boolean $key
	 * @param string $value
	 * @param string $cssFile
	 */
	private function _replaceIt($key = '', $value = '', $cssFile = '') {
		if ($key === false) {
			$value = trim ( $value );
			if (strpos ( $value, 'http' ) !== false) {
				$this->_cache ['ishttp'] = true;
				return;
			}
			if ($this->_cache ['background'])
				return;
			$this->_cache ['background'] = $this->replaceImg;
			return;
		}
		$key = $this->repairPregReplace ( $key );
		$value = $this->repairPregReplace ( $value );
		foreach ( $this->imgFilesPattern as $file => $pos ) {
			$filename = AutoSprite::getFilename ( $file );
			//css值里有background和这个图片
			if (strpos ( $value, 'background' ) > - 1 && strpos ( $value, $filename ) > - 1) {
				//如果有过滤函数，则先执行过滤方法
				if (function_exists ( $this->imgPathFilter )) {
					preg_match ( $this->_urlPattern, $value, $matches );
					if (is_array ( $matches ) && $matches [2]) {
						$result = call_user_func ( $this->imgPathFilter, $file, $filename, $cssFile );
						if ($result) {
							continue;
						}
					} else {
						continue;
					}
				}
				preg_replace ( $this->_urlPattern, "self::_replaceIt(false,'\\2')", $value );
				if ($this->_cache ['ishttp'] == true) {
					$this->_cache ['ishttp'] = false;
					continue;
				}
				$this->_cache ['key'] [] = $key;
				$value = preg_replace ( $this->_urlPattern, "", $value );
				$value = preg_replace ( $this->_backgroundImagePattern, "", $value );
				/* 
				 * 对css里还有background-position为right或者bottom作处理
				 * 如果含有bottom的话，那么当前的小图片必须合在大图的最底位置或者没有平铺使用水平方向的合并
				 * 如果含有right的话，那么当前的小图片必须是最宽的或者水平合并在最右侧的位置
				 * 现在图片合并使用文件名排序，所以可以在文件名上来进行标示
				 */
				if (preg_match ( $this->_backgroundPositionPattern, $value, $matches )) {
					$type = $matches [1];
					if ($type === 'right') {
						$value .= ";background-position:right " . $this->getPx ( $pos [1] ) . ';';
					} else {
						$value .= ";background-position:" . $this->getPx ( $pos [0] ) . 'bottom;';
					}
				} else {
					$value .= ";background-position:" . $this->getPx ( $pos [0] ) . $this->getPx ( $pos [1] ) . ';';
				}
			}
		}
		return $key . "{" . trim ( trim ( $value ), ';' ) . "}";
	}

	/**
	 * 
	 * 修复replace的时候对引号的自动转义
	 * @param string $content
	 */
	public function repairPregReplace($content = '') {
		if (! is_string ( $content ))
			return $content;
		$content = str_replace ( '\\"', '"', $content );
		return $content;
	}

	/**
	 * 
	 * 获取数值
	 * @param int $value
	 */
	public function getPx($value) {
		$value = $value == 0 ? $value : ($value . 'px');
		return $value . ' ';
	}
}