<?php
/**
 * Test for org::bovigo::vfs::vfsStreamDirectory.
 *
 * @package     bovigo_vfs
 * @subpackage  test
 */
require_once 'org/bovigo/vfs/vfsStreamDirectory.php';
require_once 'PHPUnit/Framework.php';
/**
 * Test for org::bovigo::vfs::vfsStreamDirectory.
 *
 * @package     bovigo_vfs
 * @subpackage  test
 */
class vfsStreamDirectoryTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @var  vfsStreamDirectory
     */
    protected $dir;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->dir = new vfsStreamDirectory('foo');
    }

    /**
     * assure that a directory seperator inside the name throws an exception
     *
     * @test
     * @expectedException  vfsStreamException
     */
    public function invalidCharacterInName()
    {
        $dir = new vfsStreamDirectory('foo/bar');
    }

    /**
     * test default values and methods
     *
     * @test
     */
    public function defaultValues()
    {
        $this->assertEquals(vfsStreamContent::TYPE_DIR, $this->dir->getType());
        $this->assertEquals('foo', $this->dir->getName());
        $this->assertTrue($this->dir->appliesTo('foo'));
        $this->assertTrue($this->dir->appliesTo('foo/bar'));
        $this->assertFalse($this->dir->appliesTo('bar'));
        $this->assertEquals(array(), $this->dir->getChildren());
    }

    /**
     * test renaming the directory
     *
     * @test
     */
    public function rename()
    {
        $this->dir->rename('bar');
        $this->assertEquals('bar', $this->dir->getName());
        $this->assertFalse($this->dir->appliesTo('foo'));
        $this->assertFalse($this->dir->appliesTo('foo/bar'));
        $this->assertTrue($this->dir->appliesTo('bar'));
    }

    /**
     * renaming the directory to an invalid name throws a vfsStreamException
     *
     * @test
     * @expectedException  vfsStreamException
     */
    public function renameToInvalidNameThrowsvfsStreamException()
    {
        $this->dir->rename('foo/baz');
    }

    /**
     * test checking and retrieving a non existing child
     *
     * @test
     */
    public function nonExistingChild()
    {
        $this->assertFalse($this->dir->hasChild('bar'));
        $this->assertNull($this->dir->getChild('bar'));
        $this->assertFalse($this->dir->removeChild('bar'));
        $mockChild = $this->getMock('vfsStreamContent');
        $mockChild->expects($this->any())
                  ->method('appliesTo')
                  ->will($this->returnValue(false));
        $mockChild->expects($this->any())
                  ->method('getName')
                  ->will($this->returnValue('baz'));
        $this->dir->addChild($mockChild);
        $this->assertFalse($this->dir->removeChild('bar'));
    }

    /**
     * test that adding, handling and removing of a child works as expected
     *
     * @test
     */
    public function childHandling()
    {
        $mockChild = $this->getMock('vfsStreamContent');
        $mockChild->expects($this->any())
                  ->method('getType')
                  ->will($this->returnValue(vfsStreamContent::TYPE_FILE));
        $mockChild->expects($this->any())
                  ->method('getName')
                  ->will($this->returnValue('bar'));
        $mockChild->expects($this->any())
                  ->method('appliesTo')
                  ->with($this->equalTo('bar'))
                  ->will($this->returnValue(true));
        $mockChild->expects($this->once())
                  ->method('size')
                  ->will($this->returnValue(5));
        $this->dir->addChild($mockChild);
        $this->assertTrue($this->dir->hasChild('bar'));
        $bar = $this->dir->getChild('bar');
        $this->assertSame($mockChild, $bar);
        $this->assertEquals(array($mockChild), $this->dir->getChildren());
        $this->assertEquals(0, $this->dir->size());
        $this->assertEquals(5, $this->dir->sizeSummarized());
        $this->assertTrue($this->dir->removeChild('bar'));
        $this->assertEquals(array(), $this->dir->getChildren());
        $this->assertEquals(0, $this->dir->size());
        $this->assertEquals(0, $this->dir->sizeSummarized());
    }

    /**
     * test that adding, handling and removing of a child works as expected
     *
     * @test
     */
    public function childHandlingWithSubdirectory()
    {
        $mockChild = $this->getMock('vfsStreamContent');
        $mockChild->expects($this->any())
                  ->method('getType')
                  ->will($this->returnValue(vfsStreamContent::TYPE_FILE));
        $mockChild->expects($this->any())
                  ->method('getName')
                  ->will($this->returnValue('bar'));
        $mockChild->expects($this->once())
                  ->method('size')
                  ->will($this->returnValue(5));
        $subdir = new vfsStreamDirectory('subdir');
        $subdir->addChild($mockChild);
        $this->dir->addChild($subdir);
        $this->assertTrue($this->dir->hasChild('subdir'));
        $this->assertSame($subdir, $this->dir->getChild('subdir'));
        $this->assertEquals(array($subdir), $this->dir->getChildren());
        $this->assertEquals(0, $this->dir->size());
        $this->assertEquals(5, $this->dir->sizeSummarized());
        $this->assertTrue($this->dir->removeChild('subdir'));
        $this->assertEquals(array(), $this->dir->getChildren());
        $this->assertEquals(0, $this->dir->size());
        $this->assertEquals(0, $this->dir->sizeSummarized());
    }
    
    /**
     * dd
     *
     * @test
     * @group  regression
     * @group  bug_5
     */
    public function addChildReplacesChildWithSameName_Bug_5()
    {
        $mockChild1 = $this->getMock('vfsStreamContent');
        $mockChild1->expects($this->any())
                   ->method('getType')
                   ->will($this->returnValue(vfsStreamContent::TYPE_FILE));
        $mockChild1->expects($this->any())
                   ->method('getName')
                   ->will($this->returnValue('bar'));
        $mockChild2 = $this->getMock('vfsStreamContent');
        $mockChild2->expects($this->any())
                   ->method('getType')
                   ->will($this->returnValue(vfsStreamContent::TYPE_FILE));
        $mockChild2->expects($this->any())
                   ->method('getName')
                   ->will($this->returnValue('bar'));
        $this->dir->addChild($mockChild1);
        $this->assertTrue($this->dir->hasChild('bar'));
        $this->assertSame($mockChild1, $this->dir->getChild('bar'));
        $this->dir->addChild($mockChild2);
        $this->assertTrue($this->dir->hasChild('bar'));
        $this->assertSame($mockChild2, $this->dir->getChild('bar'));
    }

    /**
     * setting and retrieving permissions for a directory
     *
     * @test
     * @group  permissions
     */
    public function permissions()
    {
        $this->assertEquals(0777, $this->dir->getPermissions());
        $this->assertSame($this->dir, $this->dir->chmod(0755));
        $this->assertEquals(0755, $this->dir->getPermissions());
    }

    /**
     * setting and retrieving permissions for a directory
     *
     * @test
     * @group  permissions
     */
    public function permissionsSet()
    {
        $this->dir = new vfsStreamDirectory('foo', 0755);
        $this->assertEquals(0755, $this->dir->getPermissions());
        $this->assertSame($this->dir, $this->dir->chmod(0700));
        $this->assertEquals(0700, $this->dir->getPermissions());
    }

    /**
     * setting and retrieving owner of a file
     *
     * @test
     * @group  permissions
     */
    public function owner()
    {
        $this->assertEquals(vfsStream::getCurrentUser(), $this->dir->getUser());
        $this->assertTrue($this->dir->isOwnedByUser(vfsStream::getCurrentUser()));
        $this->assertSame($this->dir, $this->dir->chown(vfsStream::OWNER_USER_1));
        $this->assertEquals(vfsStream::OWNER_USER_1, $this->dir->getUser());
        $this->assertTrue($this->dir->isOwnedByUser(vfsStream::OWNER_USER_1));
    }

    /**
     * setting and retrieving owner group of a file
     *
     * @test
     * @group  permissions
     */
    public function group()
    {
        $this->assertEquals(vfsStream::getCurrentGroup(), $this->dir->getGroup());
        $this->assertTrue($this->dir->isOwnedByGroup(vfsStream::getCurrentGroup()));
        $this->assertSame($this->dir, $this->dir->chgrp(vfsStream::GROUP_USER_1));
        $this->assertEquals(vfsStream::GROUP_USER_1, $this->dir->getGroup());
        $this->assertTrue($this->dir->isOwnedByGroup(vfsStream::GROUP_USER_1));
    }
}
?>