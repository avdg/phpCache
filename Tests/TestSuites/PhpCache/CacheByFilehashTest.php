<?php

require_once dirname(__FILE__) . '/../../../Lib/PhpCache/CacheByFilehash.php';

class CacheFilehashTest extends PHPUnit_Framework_TestCase
{
    private $_cache;
    private $_cleanupMocks = false;
    private $_dir         = array();
    private $_file        = array();
    private $_path;

    public function setUp()
    {
        $this->_cache = new CacheByFilehash();
        $this->_path  = dirname(__FILE__) . '/../_mocks';

        $this->_dir = array(
            'cache'                => $this->_path . '/cache',
            'cacheExtraDir'        => $this->_path . '/cache/dir',
            'files'                => $this->_path . '/files',
        );

        $this->_file = array(
            'bar'        => $this->_path . '/files/bar.txt',
            'foo'        => $this->_path . '/files/foo.txt',
            'barCache'   => $this->_path . '/cache/b277e0b295e6982a57731ef4068eebd950bcf741',
            'fooCache'   => $this->_path . '/cache/4306b576bb5db0d0304b6266aaab5fb1d27cae41',
            'wrongFile1' => $this->_path . '/cache/ga13413415465655244552451443353413434142',
            'wrongFile2' => $this->_path . '/cache/1343545ab15452f15452454524526452524545545',
            'wrongFile3' => $this->_path . '/cache/14134135152452',
        );
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
            'cacheDirectory' => $this->_dir['cache'],
        ));
    }

    public function tearDownMocks()
    {
        $this->_cleanupMocks = false;

        $iterator = new DirectoryIterator($this->_dir['cache']);

        foreach ($iterator as $directoryItem) {
            if ($directoryItem->isDir()) {
                continue;
            }

            unlink($this->_dir['cache'] . '/' . $directoryItem->getFileName());
        }

        if (is_dir($this->_dir['cacheExtraDir'])) {
            rmdir($this->_dir['cacheExtraDir']);
        }
    }

    public function testSettingCacheDirectory()
    {
        $this->assertEquals(false, $this->_cache->getConfig('cacheDirectory'));

        $this->_cache->setConfig(array(
            'cacheDirectory' => $this->_dir['cache'],
        ));

        $this->assertEquals($this->_dir['cache'],
            $this->_cache->getConfig('cacheDirectory')
        );
    }

    public function testUnexistingSettings()
    {
        // temp fix because false positive
        $this->_cache->setConfig(
            array('cacheDirectory' => $this->_dir['cache'])
        );

        $this->assertEquals(false, $this->_cache->getConfig('unknow'));
        $this->assertEquals(null,  $this->_cache->getConfig(array('foo', 'bar')));
    }

    public function testGetHashFile()
    {
        $this->assertEquals('4306b576bb5db0d0304b6266aaab5fb1d27cae41',
            $this->_cache->getHashFile($this->_file['foo'])
        );
    }

    public function testGetObjectionLocation()
    {
        $this->setUpMocks();

        $this->assertEquals($this->_file['fooCache'],
            $this->_cache->getObjectLocation($this->_file['foo'])
        );
    }

    public function testAddCacheWithIsCacheHit()
    {
        $this->setUpMocks();

        $this->assertEquals(false, file_exists($this->_file['fooCache']));
        $this->assertEquals(false, $this->_cache->isCacheHit($this->_file['foo']));

        $this->_cache->addCache($this->_file['foo'], 'foo');
        $this->assertEquals(true, file_exists($this->_file['fooCache']));
        $this->assertEquals(true, $this->_cache->isCacheHit($this->_file['foo']));
    }

    public function testGetCache()
    {
        $this->setUpMocks();

        $this->assertEquals(false, $this->_cache->getCache($this->_file['foo']));

        $this->_cache->addCache($this->_file['foo'], 'foo');
        $this->assertEquals('foo', $this->_cache->getCache($this->_file['foo']));
    }

    public function testKeep()
    {
        $this->setUpMocks();

        file_put_contents($this->_file['wrongFile1'], 'Important');
        file_put_contents($this->_file['wrongFile2'], 'Important');
        file_put_contents($this->_file['wrongFile3'], 'Important');

        mkdir($this->_dir['cacheExtraDir']);

        $this->_cache->addCache($this->_file['bar'], 'bar');
        $this->_cache->addCache($this->_file['foo'], 'foo');
        $this->assertEquals(true, file_exists($this->_file['barCache']));
        $this->assertEquals(true, file_exists($this->_file['fooCache']));

        $this->_cache->keep(array(
            $this->_cache->getHashFile($this->_file['bar'])
        ));
        $this->assertEquals(true,  file_exists($this->_file['barCache']));
        $this->assertEquals(false, file_exists($this->_file['fooCache']));

        $this->assertEquals(true, file_exists($this->_file['wrongFile1']));
        $this->assertEquals(true, file_exists($this->_file['wrongFile2']));
        $this->assertEquals(true, file_exists($this->_file['wrongFile3']));
        $this->assertEquals(true, is_dir($this->_dir['cacheExtraDir']));

        rmdir($this->_dir['cacheExtraDir']);
    }

    public function testClean()
    {
        $this->setUpMocks();

        $this->_cache->addCache($this->_file['bar'], 'bar');
        $this->_cache->addCache($this->_file['foo'], 'foo');
        $this->assertEquals(true, file_exists($this->_file['barCache']));
        $this->assertEquals(true, file_exists($this->_file['fooCache']));

        $this->_cache->clean();
        $this->assertEquals(false, file_exists($this->_file['barCache']));
        $this->assertEquals(false, file_exists($this->_file['fooCache']));
    }

    public function testExpectExceptions()
    {
        try {
            $this->_cache->addCache($this->_file['foo'], 'foo');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }

        try {
            $this->_cache->getCache($this->_file['foo']);
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }

        try {
            $this->_cache->isCacheHit($this->_file['foo']);
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
            $this->_cache->keep(array($this->_file['foo']));
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }

        try {
            $this->_cache->getObjectLocation($this->_file['foo']);
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Cache directory not set', $e->getMessage());
        }
    }
}
