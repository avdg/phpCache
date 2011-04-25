<?php
/**
 * PhpCache library
 *
 * @category  PhpCache
 * @package   CacheByFileHash
 * @copyright Copyright (c) 2011 Anthony Van de Gejuchte
 */

/**
 * Stores data of parsing results by hash of the file.
 * This way, we can guarantee that the results are matched with the right files.
 *
 * This class is making use of the sha1 algoritme, since the md5 algoritme is
 * probably too broken to use, despite being pretty usable as just a file hash.
 * See http://www.mscs.dal.ca/~selinger/md5collision/
 *
 * Sha1 is fine because git is using it and will have a bigger change of
 * collisions (directories and commits are also hashed, and git keeps track of
 * directories and files from every commit). So please only conclude that sha1
 * is unusable when many people complain about *real* collisions in git.
 *
 * Checklist:
 *     - Caches results of parsed files
 *     - Results are only content dependent (not dependent on filelocation)
 *     - Results are consistence (output doesn't change with the same input)
 *     - Use a directory that is only used by the application (like /.cacheXYZ/)
 *     - Make sure the cache directory exists before using this class
 *
 * Attention:
 *     - All files of the same content have the same hash, filenames are not
 *       included in the hash of the file.
 *     - The content is *not* hashed, and the content can't be validated as
 *       valid, unless other validation methods are used. The hash only
 *       refers to the original file, from where the results comes from.
 *     - Make sure to use the right cache for the right applications.
 *       Two programs with different cache results will produce the
 *       same list of objects, but with a different content!
 *
 * @category  PhpCache
 * @package   CacheByFileHash
 * @copyright Copyright (c) 2011 Anthony Van de Gejuchte
 */
class CacheByFileHash
{
    /**
     * Cache location
     *
     * @var string
     */
    protected $cacheDirectory;

    /**
     * Constructor
     *
     * Accepts a configuration array
     *
     * @param array $config Configuration array
     *
     * @see setConfig For configuration options
     */
    public function __construct(array $config = array())
    {
        $this->setConfig($config);
    }

    /**
     * Set new configuration settings
     *
     * Current configuration settings:
     *
     * cacheDirectory: The location where all cache objects are stored
     *
     * @param array $config Configuration array
     *
     * @return void
     */
    public function setConfig(array $config)
    {
        if (isset($config['cacheDirectory'])) {
            $this->cacheDirectory = $config['cacheDirectory'];
        }
    }

    /**
     * Get a configuration setting, false if option not found
     *
     * @param string $option Options to get
     *
     * @return mixed
     *
     * @see setConfig For configuration options
     */
    public function getConfig($option)
    {
        if (is_string($option)) {
            if ($option == 'cacheDirectory') {
                if (!isset($this->cacheDirectory)) {
                    return false;
                }
                return $this->cacheDirectory;
            }
            return false;
        }
    }

    /**
     * Adds a file to the cache database
     *
     * @param string $filename Full path to the file
     * @param string $data     Data to be stored
     *
     * @return void
     */
    public function addCache($filename, $data)
    {
        $location = $this->getObjectLocation($filename);
        file_put_contents($location, $data);
    }

    /**
     * Gets the cache data of the file if the file was found in the cache
     *
     * @param string $filename Full path to the file
     * @param mixed  $return   Default return value if file was not found
     *
     * @return mixed
     */
    public function getCache($filename, $return = false)
    {
        if ($this->isCacheHit($filename)) {
            return file_get_contents($this->getObjectLocation($filename));
        }

        return $return;
    }

    /**
     * Returns true if the file was found in the cache
     *
     * @param string $filename Full path to the file
     *
     * @return bool
     */
    public function isCacheHit($filename)
    {
        return file_exists($this->getObjectLocation($filename));
    }

    /**
     * Cleans up all objects
     *
     * @return void
     */
    public function clean()
    {
        $this->keep(array());
    }

    /**
     * Removes all objects not given in the list
     *
     * @param array $items List of hashes from objects to keep
     *
     * @return void
     */
    public function keep(array $items)
    {
        if (!isset($this->cacheDirectory)) {
            throw new Exception('Cache directory not set');
        }

        $iterator = new DirectoryIterator($this->cacheDirectory);

        foreach ($iterator as $directoryItem) {
            if ($directoryItem->isDir()) {
                continue;
            }

            $file = $directoryItem->getFileName();

            if (strlen($file) != 40 
                || strspn($file, '0123456789abcdef') != 40
            ) {
                continue;
            }

            if (in_array($file, $items)) {
                continue;
            }

            unlink($this->cacheDirectory . '/' . $file);
        }
    }

    /**
     * Get the full path of the object, based on the hash of the file
     *
     * @param string $filename Full path to the file
     *
     * @return string
     */
    public function getObjectLocation($filename)
    {
        if (!is_string($this->cacheDirectory)) {
            throw new Exception('Cache directory not set');
        }

        return $this->cacheDirectory . '/' . $this->getHashFile($filename);
    }

    /**
     * Get hash of a file
     *
     * @param string $filename Full path to the file
     *
     * @return string
     */
    public function getHashFile($filename)
    {
        return sha1_file($filename);
    }
}
