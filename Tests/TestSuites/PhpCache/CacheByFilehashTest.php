<?php

require_once dirname(__FILE__) . '/../../../Lib/PhpCache/CacheByFilehash.php';

class CacheFilehashTest extends PHPUnit_Framework_TestCase
{
    private $_cache;
    private $_cleanupMocks = false;
    private $_path;

    public function setUp()
    {
        $this->_cache = new CacheByFileHash();
        $this->_path   = dirname(__FILE__) . '/../_mocks';
    }

    public function tearDown()
    {
        if ($this->_cleanupMocks) {
            $this->tearDownMocks();
        }
    }

    public function setUpMocks()
    {
        $this->_cleanupMocks = true;

        $this->_cache->setConfig(array(
            'cacheDirectory' => $this->_path . '/cache',
        ));
    }

    public function tearDownMocks()
    {
        $this->_cleanupMocks = false;

        $iterator = new DirectoryIterator($this->_path . '/cache');

        foreach ($iterator as $directoryItem) {
            if ($directoryItem->isDir()) {
                continue;
            }

            unlink($this->_path . '/cache/' . $directoryItem->getFileName());
        }
    }

    public function testSettingCacheDirectory()
    {
        $this->assertEquals(false, $this->_cache->getConfig('cacheDirectory'));

        $this->_cache->setConfig(array(
            'cacheDirectory' => $this->_path . '/cache',
        ));

        $this->assertEquals($this->_path . '/cache',
            $this->_cache->getConfig('cacheDirectory')
        );
    }

    public function testUnexistingSettings()
    {
        // temp fix because false positive
        $this->_cache->setConfig(
            array('cacheDirectory' => $this->_path . '/cache')
        );

        $this->assertEquals(false, $this->_cache->getConfig('unknow'));
        $this->assertEquals(null,  $this->_cache->getConfig(array('foo', 'bar')));
    }

    public function testGetHashFile()
    {
        $this->assertEquals('4306b576bb5db0d0304b6266aaab5fb1d27cae41',
            $this->_cache->getHashFile($this->_path . '/files/foo.txt')
        );
    }

    public function testGetObjectionLocation()
    {
        $this->setUpMocks();

        $file  = $this->_path . '/files/foo.txt';
        $cache = $this->_path . '/cache/4306b576bb5db0d0304b6266aaab5fb1d27cae41';

        $this->assertEquals($cache, $this->_cache->getObjectLocation($file));
    }

    public function testAddCacheWithIsCacheHit()
    {
        $this->setUpMocks();

        $file  = $this->_path . '/files/foo.txt';
        $cache = $this->_path . '/cache/4306b576bb5db0d0304b6266aaab5fb1d27cae41';
        $this->assertEquals(false, file_exists($cache));
        $this->assertEquals(false, $this->_cache->isCacheHit($file));

        $this->_cache->addCache($file, 'foo');
        $this->assertEquals(true, file_exists($cache));
        $this->assertEquals(true, $this->_cache->isCacheHit($file));
    }

    public function testGetCache()
    {
        $this->setUpMocks();

        $file  = $this->_path . '/files/foo.txt';
        $cache = $this->_path . '/cache/4306b576bb5db0d0304b6266aaab5fb1d27cae41';
        $this->assertEquals(false, $this->_cache->getCache($file));

        $this->_cache->addCache($file, 'foo');
        $this->assertEquals('foo', $this->_cache->getCache($file));
    }

    public function testKeep()
    {
        $this->setUpMocks();

        $bar        = $this->_path . '/files/bar.txt';
        $foo        = $this->_path . '/files/foo.txt';
        $barCache   = $this->_path . '/cache/b277e0b295e6982a57731ef4068eebd950bcf741';
        $fooCache   = $this->_path . '/cache/4306b576bb5db0d0304b6266aaab5fb1d27cae41';
        $wrongFile1 = $this->_path . '/cache/ga13413415465655244552451443353413434142';
        $wrongFile2 = $this->_path . '/cache/1343545ab15452f15452454524526452524545545';
        $wrongFile3 = $this->_path . '/cache/14134135152452';
        $dir        = $this->_path . '/cache/dir';

        file_put_contents($wrongFile1, 'Important');
        file_put_contents($wrongFile2, 'Important');
        file_put_contents($wrongFile3, 'Important');
        mkdir($dir);

        $this->_cache->addCache($bar, 'bar');
        $this->_cache->addCache($foo, 'foo');
        $this->assertEquals(true, file_exists($barCache));
        $this->assertEquals(true, file_exists($fooCache));

        $this->_cache->keep(array($this->_cache->getHashFile($bar)));
        $this->assertEquals(true,  file_exists($barCache));
        $this->assertEquals(false, file_exists($fooCache));

        $this->assertEquals(true, file_exists($wrongFile1));
        $this->assertEquals(true, file_exists($wrongFile2));
        $this->assertEquals(true, file_exists($wrongFile3));
        $this->assertEquals(true, is_dir($dir));

        rmdir($dir);
    }

    public function testClean()
    {
        $this->setUpMocks();

        $bar      = $this->_path . '/files/bar.txt';
        $foo      = $this->_path . '/files/foo.txt';
        $barCache = $this->_path . '/cache/b277e0b295e6982a57731ef4068eebd950bcf741';
        $fooCache = $this->_path . '/cache/4306b576bb5db0d0304b6266aaab5fb1d27cae41';

        $this->_cache->addCache($bar, 'bar');
        $this->_cache->addCache($foo, 'foo');
        $this->assertEquals(true, file_exists($barCache));
        $this->assertEquals(true, file_exists($fooCache));

        $this->_cache->clean();
        $this->assertEquals(false, file_exists($barCache));
        $this->assertEquals(false, file_exists($fooCache));
    }

    public function testExpectExceptions()
    {
        $pathFiles = $this->_path . '/../files';

        try {
            $this->_cache->addCache($pathFiles . '/foo.txt', 'foo');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }

        try {
            $this->_cache->getCache($pathFiles . '/foo.txt');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }

        try {
            $this->_cache->isCacheHit($pathFiles . '/foo.txt');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }

        try {
            $this->_cache->clean();
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }

        try {
            $this->_cache->keep(array($pathFiles . '/foo.txt'));
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }

        try {
            $this->_cache->getObjectLocation($pathFiles . '/foo.txt');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }
    }
}
