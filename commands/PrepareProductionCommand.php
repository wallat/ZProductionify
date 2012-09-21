<?php
/**
 * This command copies all the assets into assets.production and compress them
 *
 * It does:
 *     1. foreach assets (main assets and the widget assets), create the assets.production folder (delete it if it is already there)
 *     2. Use yui compressor to compress every js and css files and output them to the production folder.
 *     3. Use pngcrush to compress every png files and output them to the production folder.
 *     4. Directly copy the remaining files to the production folder.
 *     5. Add those production folders to the git repository.
 *     6. Commit git.
 */
class PrepareProductionCommand extends CConsoleCommand {
    public $yuiJarPath = null;

    public function run() {
        // collect all the asset paths
        echo "Start to collect the asset paths. \n";

        $assetPaths = array();
        $assetPaths[] = Yii::getPathOfAlias('application.assets');
        $widgetBasePath = Yii::getPathOfAlias('application.widgets');
        if ($dh = opendir($widgetBasePath)) {
            while (($d=readdir($dh)) !== false) {
                if ($this->isDot($d)) {
                    continue;
                }

                $assetPath = $widgetBasePath.DIRECTORY_SEPARATOR.$d.DIRECTORY_SEPARATOR.'assets';
                if (is_dir($assetPath)) {
                    $assetPaths[] = $assetPath;
                }
            }
            closedir($dh);
        }

        // compress each path
        echo "Start to compress all the jses and csses to production folders.\n";
        foreach ($assetPaths as $path) {
            $this->compressFolder($path, $path.DIRECTORY_SEPARATOR.'production');
        }

        // Add to git
        echo "Now add production folders to git \n";
        foreach ($assetPaths as $path) {
            $p = $path.DIRECTORY_SEPARATOR.'production';
            echo "    Add folder $p\n";
            exec("git add $p");
        }

        echo "Now commit to git\n";
        exec('git commit -a -m "Generated production client-side files."');

        echo "All done\n";
    }

    /**
     * Recursively compress files which inside a folder
     *
     * @param string $source The input directory path
     * @param string $destination The output directory path
     * @return void
     */
    public function compressFolder($source, $destination) {
        if ( !is_dir($source) || !is_readable($source)) {
            throw new Exception("$source is not a directory or is not readable");
        }

        // create the destination directory
        if (is_dir($destination)) {
            $this->removeDir($destination);
        }
        mkdir($destination);
        chmod($destination, 0777);

        if (!is_writable($destination)) {
            throw new Exception("$destination is not writable");
        }

        if (($dh=opendir($source))) {
            while (($f=readdir($dh))) {
                $path = $source.DIRECTORY_SEPARATOR.$f;
                $outPath = $destination.DIRECTORY_SEPARATOR.$f;

                if (is_file($path)) {
                    $type = $this->getExt($path);

                    if (in_array($type, array('css','js', 'png'))) { // supported file types
                        echo "    Compress $path ...";
                        $this->compress($type, $path, $outPath);
                        $inSize = round(filesize($path)/1024, 1);
                        $outSize = round(filesize($outPath)/1024, 1);
                        echo sprintf("Done. \t %.1fKB/%.1fKB (%.0f%%). \n", $inSize, $outSize, ($inSize-$outSize)*100/$inSize);
                    } elseif ($type=='less') {
                        // we need to compile it and then compress it
                        echo "    Compress $path ...";

                        // create the temp file name
                        $compiledPath = Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.'less-'.uniqid().'.css';

                        // compile it to normal css file
                        $this->compress('less', $path, $compiledPath);

                        // prepare the outPath
                        $outPath = preg_replace('/\.less$/', '.css', $outPath);

                        // compress the compiled css file
                        $this->compress('css', $compiledPath, $outPath);

                        $inSize = round(filesize($compiledPath)/1024, 1);
                        $outSize = round(filesize($outPath)/1024, 1);

                        // delete temp file
                        exec("rm $compiledPath");

                        echo sprintf("Done. \t %.1fKB/%.1fKB (%.0f%%). \n",
                            $inSize,
                            $outSize,
                            ($inSize ? ($inSize-$outSize)*100/$inSize : 0)
                        );
                    } else {
                        // for un-support file types. just directly copy it
                        echo "    Copy $path ... ";
                        copy($path, $outPath);
                        echo "Done\n";
                    }
                }
                if (is_dir($path) && !$this->isDot($f) && $f!='production') {
                    $this->compressFolder($path, $destination.DIRECTORY_SEPARATOR.$f);
                }
            }
        }
    }

    /**
     * Is . or ..
     *
     * @param string $s
     * @return bool
     */
    public function isDot($s) {
        return in_array($s, array('.', '..'));
    }

    /**
     * Use yui compressor to compress a file
     *
     * @param string $type js or css
     * @param string $inPath The input file
     * @param string $outPath The output file path
     * @return void
     */
    public function compress($type, $inPath, $outPath) {
        switch ($type) {
            case 'png':
                return exec("/usr/bin/env pngcrush -brute $inPath $outPath");
                break;

            case 'js':
            case 'css':
                if (empty($this->yuiJarPath)) {
                    $this->yuiJarPath = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'bins'.DIRECTORY_SEPARATOR.'yuicompressor-2.4.7.jar');
                }
                $jarPath = $this->yuiJarPath;
                return exec("/usr/bin/env java -jar $jarPath $inPath -o $outPath --charset utf-8 --type $type --line-break 5000");
                break;
            case 'less':
                return Yii::app()->lessc->compile($inPath, $outPath);
                break;

            default:
                # code...
                break;
        }

        return false;
    }

    /**
     * Fetch the extension name
     *
     * @param string $path
     * @return string extension
     */
    public function getExt($path) {
        $infos = pathinfo($path);
        return isset($infos['extension']) ? $infos['extension'] : null;
    }

    /**
     * Remove a directory
     *
     * @param string $path
     * @return bool
     */
    public function removeDir($path) {
        if (is_dir($path)) {
            exec("rm $path -rf");
        }
    }
}
