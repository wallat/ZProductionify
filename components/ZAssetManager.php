<?php
/**
 * Help you to prepare production mode
 *
 * Changes from original CAssetManager:
 *   - Publish the production folder when useProductionAssets is true
 *   - Add the version number when creating hash
 */
class ZAssetManager extends CAssetManager {
    /**
     * @var array published assets
     */
    protected $_published=array();

    /**
     * Set this value to true only when you already created production folders in every asset folder.
     * The the assetManager will read the assets.production rather than assets folder.
     *
     * @var bool
     */
    public $useProductionAssets = false;

    /**
     * Use this value to create different hash between different version systems.
     *
     * @var string
     */
    public $version = '';

    /**
     * Publish the production folder when useProductionAssets is true
     *
     * @see http://www.yiiframework.com/doc/api/1.1/CAssetManager#publish-detail
     */
    public function publish($path,$hashByName=false,$level=-1,$forceCopy=false) {
        if ($this->useProductionAssets) {
            $path = str_replace(DIRECTORY_SEPARATOR.'assets', DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'production', $path);
        }

        if (YII_DEBUG) {
            return $this->devPublish($path, $hashByName, $level, $forceCopy);
        } else {
            return parent::publish($path, $hashByName, $level, $forceCopy);
        }
    }

    /**
     * The newer version also add the given version number when creating hash
     *
     * @see http://www.yiiframework.com/doc/api/1.1/CAssetManager#hash-detail
     */
	protected function hash($path) {
		return sprintf('%x',crc32($path.Yii::getVersion().$this->version));
	}

    /**
     * This function is almost the same as the original one.
     * But it additional checks the lastest modifiy time of folders to
     * ensure the new files will be published.
     *
     * @see http://www.yiiframework.com/doc/api/1.1/CAssetManager#publish-detail
     */
    public function devPublish($path,$hashByName=false,$level=-1,$forceCopy=false) {
        if (isset($this->_published[$path]))
            return $this->_published[$path];
        else if (($src=realpath($path))!==false) {
            if (is_file($src)) {
                $dir = $this->hash($hashByName ? basename($src) : dirname($src));
                $fileName = basename($src);
                $dstDir = $this->getBasePath().DIRECTORY_SEPARATOR.$dir;
                $dstFile = $dstDir.DIRECTORY_SEPARATOR.$fileName;

                if ($this->linkAssets) {
                    if (!is_file($dstFile)) {
                        if (!is_dir($dstDir)) {
                            mkdir($dstDir);
                            @chmod($dstDir, $this->newDirMode);
                        }
                        symlink($src,$dstFile);
                    }
                } else {
                    $this->copyFileIfNewer($src, $dstFile, $forceCopy);
                }

                return $this->_published[$path]=$this->getBaseUrl()."/$dir/$fileName";
            } else if (is_dir($src)) {
                $dir = $this->hash($hashByName ? basename($src) : $src);
                $dstDir = $this->getBasePath().DIRECTORY_SEPARATOR.$dir;

                if ($this->linkAssets) {
                    if (!is_dir($dstDir))
                        symlink($src,$dstDir);
                } else {
                    $this->publishFolder($src, $dstDir, $forceCopy);
                }

                return $this->_published[$path]=$this->getBaseUrl().'/'.$dir;
            }
        }

        throw new CException(Yii::t('yii','The asset "{asset}" to be published does not exist.',
            array('{asset}'=>$path)));
    }

    /**
     * Both path should be the existing folder
     *
     * @param  [type] $srcDir [description]
     * @param  [type] $dstDir [description]
     * @return [type]         [description]
     */
    public function publishFolder($srcDir, $dstDir, $forceCopy=false) {
        $srcDir = rtrim($srcDir, DIRECTORY_SEPARATOR);
        $dstDir = rtrim($dstDir, DIRECTORY_SEPARATOR);

        if ( !is_dir($srcDir)) {
            throw new Exception("srcDir $srcDir is not a valid folder path");
        }

        if (is_dir($dstDir)) {
            $it = new DirectoryIterator($srcDir);

            foreach($it as $f) {
                if ($it->isDot()) {
                    // do nothing
                } else if ($f->isDir()) {
                    $bn = $f->getBasename();
                    $this->publishFolder($srcDir.DIRECTORY_SEPARATOR.$bn, $dstDir.DIRECTORY_SEPARATOR.$bn, $forceCopy);
                } else {
                    $fn = $f->getFilename();
                    $src = $srcDir.DIRECTORY_SEPARATOR.$fn;
                    $dst = $dstDir.DIRECTORY_SEPARATOR.$fn;
                    $this->copyFileIfNewer($src, $dst, $forceCopy);
                }
            }
        } else {
            CFileHelper::copyDirectory($srcDir, $dstDir, array(
                'exclude' => $this->excludeFiles,
                'level' => -1,
                'newDirMode' => $this->newDirMode,
                'newFileMode' => $this->newFileMode,
            ));
        }
    }

    /**
     * Copy the file into the destination place if its newer
     *
     * @param  string  $src
     * @param  string  $dst
     * @param  boolean $forceCopy
     */
    public function copyFileIfNewer($src, $dst, $forceCopy=false) {
        if (@filemtime($dst)<@filemtime($src) || $forceCopy) {
            copy($src, $dst);
            @chmod($dst, $this->newFileMode);
        }
    }
}
