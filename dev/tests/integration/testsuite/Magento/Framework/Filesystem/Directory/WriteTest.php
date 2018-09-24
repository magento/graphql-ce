<?php
/**
 * Test for \Magento\Framework\Filesystem\Directory\Write
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Filesystem\Directory;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem\DriverPool;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class ReadTest
 * Test for Magento\Framework\Filesystem\Directory\Read class
 */
class WriteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test data to be cleaned
     *
     * @var array
     */
    private $testDirectories = [];

    /**
     * Test instance of Read
     */
    public function testInstance()
    {
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        $this->assertTrue($dir instanceof ReadInterface);
        $this->assertTrue($dir instanceof WriteInterface);
    }

    /**
     * Test for create method
     *
     * @dataProvider createProvider
     * @param string $basePath
     * @param int $permissions
     * @param string $path
     */
    public function testCreate($basePath, $permissions, $path)
    {
        $directory = $this->getDirectoryInstance($basePath, $permissions);
        $this->assertTrue($directory->create($path));
        $this->assertTrue($directory->isExist($path));
    }

    /**
     * Data provider for testCreate
     *
     * @return array
     */
    public function createProvider()
    {
        return [
            ['newDir1', 0777, "newDir1"],
            ['newDir1', 0777, "root_dir1/subdir1/subdir2"],
            ['newDir2', 0777, "root_dir2/subdir"],
            ['newDir1', 0777, "."]
        ];
    }

    public function testCreateOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        try {
            $dir->create('../../outsideDir');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->create('//./..///../outsideDir');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->create('\..\..\outsideDir');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(3, $exceptions);
    }

    /**
     * Test for delete method
     *
     * @dataProvider deleteProvider
     * @param string $path
     */
    public function testDelete($path)
    {
        $directory = $this->getDirectoryInstance('newDir', 0777);
        $directory->create($path);
        $this->assertTrue($directory->isExist($path));
        $directory->delete($path);
        $this->assertFalse($directory->isExist($path));
    }

    /**
     * Data provider for testDelete
     *
     * @return array
     */
    public function deleteProvider()
    {
        return [['subdir'], ['subdir/subsubdir']];
    }

    public function testDeleteOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        try {
            $dir->delete('../../Directory');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->delete('//./..///../Directory');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->delete('\..\..\Directory');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(3, $exceptions);
    }

    /**
     * Test for rename method (in scope of one directory instance)
     *
     * @dataProvider renameProvider
     * @param string $basePath
     * @param int $permissions
     * @param string $name
     * @param string $newName
     */
    public function testRename($basePath, $permissions, $name, $newName)
    {
        $directory = $this->getDirectoryInstance($basePath, $permissions);
        $directory->touch($name);
        $created = $directory->read();
        $directory->renameFile($name, $newName);
        $renamed = $directory->read();
        $this->assertTrue(in_array($name, $created));
        $this->assertTrue(in_array($newName, $renamed));
        $this->assertFalse(in_array($name, $renamed));
    }

    /**
     * Data provider for testRename
     *
     * @return array
     */
    public function renameProvider()
    {
        return [['newDir1', 0777, 'first_name.txt', 'second_name.txt']];
    }

    public function testRenameOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        try {
            $dir->renameFile('../../Directory/ReadTest.php', 'RenamedTest');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->renameFile(
                '//./..///../Directory/ReadTest.php',
                'RenamedTest'
            );
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->renameFile('\..\..\Directory\ReadTest.php', 'RenamedTest');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(3, $exceptions);
    }

    /**
     * Test for rename method (moving to new directory instance)
     *
     * @dataProvider renameTargetDirProvider
     * @param string $firstDir
     * @param string $secondDir
     * @param int $permission
     * @param string $name
     * @param string $newName
     */
    public function testRenameTargetDir($firstDir, $secondDir, $permission, $name, $newName)
    {
        $dir1 = $this->getDirectoryInstance($firstDir, $permission);
        $dir2 = $this->getDirectoryInstance($secondDir, $permission);

        $dir1->touch($name);
        $created = $dir1->read();
        $dir1->renameFile($name, $newName, $dir2);
        $oldPlace = $dir1->read();

        $this->assertTrue(in_array($name, $created));
        $this->assertFalse(in_array($name, $oldPlace));
    }

    /**
     * Data provider for testRenameTargetDir
     *
     * @return array
     */
    public function renameTargetDirProvider()
    {
        return [['dir1', 'dir2', 0777, 'first_name.txt', 'second_name.txt']];
    }

    /**
     * Test for copy method (copy in scope of one directory instance)
     *
     * @dataProvider renameProvider
     * @param string $basePath
     * @param int $permissions
     * @param string $name
     * @param string $newName
     */
    public function testCopy($basePath, $permissions, $name, $newName)
    {
        $directory = $this->getDirectoryInstance($basePath, $permissions);
        $file = $directory->openFile($name, 'w+');
        $file->close();
        $directory->copyFile($name, $newName);
        $this->assertTrue($directory->isExist($name));
        $this->assertTrue($directory->isExist($newName));
    }

    /**
     * Data provider for testCopy
     *
     * @return array
     */
    public function copyProvider()
    {
        return [
            ['newDir1', 0777, 'first_name.txt', 'second_name.txt'],
            ['newDir1', 0777, 'subdir/first_name.txt', 'subdir/second_name.txt']
        ];
    }

    public function testCopyOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        $dir->touch('test_file_for_copy_outside.txt');
        try {
            $dir->copyFile('../../Directory/ReadTest.php', 'CopiedTest');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->copyFile(
                '//./..///../Directory/ReadTest.php',
                'CopiedTest'
            );
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->copyFile('\..\..\Directory\ReadTest.php', 'CopiedTest');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->copyFile(
                'test_file_for_copy_outside.txt',
                '../../Directory/copied_outside.txt'
            );
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(4, $exceptions);
    }

    /**
     * Test for copy method (copy to another directory instance)
     *
     * @dataProvider copyTargetDirProvider
     * @param string $firstDir
     * @param string $secondDir
     * @param int $permission
     * @param string $name
     * @param string $newName
     */
    public function testCopyTargetDir($firstDir, $secondDir, $permission, $name, $newName)
    {
        $dir1 = $this->getDirectoryInstance($firstDir, $permission);
        $dir2 = $this->getDirectoryInstance($secondDir, $permission);

        $file = $dir1->openFile($name, 'w+');
        $file->close();
        $dir1->copyFile($name, $newName, $dir2);

        $this->assertTrue($dir1->isExist($name));
        $this->assertTrue($dir2->isExist($newName));
    }

    /**
     * Data provider for testCopyTargetDir
     *
     * @return array
     */
    public function copyTargetDirProvider()
    {
        return [
            ['dir1', 'dir2', 0777, 'first_name.txt', 'second_name.txt'],
            ['dir1', 'dir2', 0777, 'subdir/first_name.txt', 'subdir/second_name.txt']
        ];
    }

    /**
     * Test for changePermissions method
     */
    public function testChangePermissions()
    {
        $directory = $this->getDirectoryInstance('newDir1', 0777);
        $directory->create('test_directory');
        $this->assertTrue($directory->changePermissions('test_directory', 0644));
    }

    public function testChangePermissionsOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        try {
            $dir->changePermissions('../../Directory', 0777);
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->changePermissions('//./..///../Directory', 0777);
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->changePermissions('\..\..\Directory', 0777);
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(3, $exceptions);
    }

    /**
     * Test for changePermissionsRecursively method
     */
    public function testChangePermissionsRecursively()
    {
        $directory = $this->getDirectoryInstance('newDir1', 0777);
        $directory->create('test_directory');
        $directory->create('test_directory/subdirectory');
        $directory->writeFile('test_directory/subdirectory/test_file.txt', 'Test Content');

        $this->assertTrue($directory->changePermissionsRecursively('test_directory', 0777, 0644));
    }

    public function testChangePermissionsRecursivelyOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        try {
            $dir->changePermissionsRecursively('../foo', 0777, 0777);
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->changePermissionsRecursively('//./..///foo', 0777, 0777);
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->changePermissionsRecursively('\..\foo', 0777, 0777);
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(3, $exceptions);
    }

    /**
     * Test for touch method
     *
     * @dataProvider touchProvider
     * @param string $basePath
     * @param int $permissions
     * @param string $path
     * @param int $time
     */
    public function testTouch($basePath, $permissions, $path, $time)
    {
        $directory = $this->getDirectoryInstance($basePath, $permissions);
        $directory->openFile($path);
        $this->assertTrue($directory->touch($path, $time));
        $this->assertEquals($time, $directory->stat($path)['mtime']);
    }

    /**
     * Data provider for testTouch
     *
     * @return array
     */
    public function touchProvider()
    {
        return [
            ['test_directory', 0777, 'touch_file.txt', time() - 3600],
            ['test_directory', 0777, 'subdirectory/touch_file.txt', time() - 3600]
        ];
    }

    public function testTouchOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        try {
            $dir->touch('../../foo.tst');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->touch('//./..///../foo.tst');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->touch('\..\..\foo.tst');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(3, $exceptions);
    }

    /**
     * Test isWritable method
     */
    public function testIsWritable()
    {
        $directory = $this->getDirectoryInstance('newDir1', 0777);
        $directory->create('bar');
        $this->assertFalse($directory->isWritable('not_existing_dir'));
        $this->assertTrue($directory->isWritable('bar'));
    }

    public function testIsWritableOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        try {
            $dir->isWritable('../../Directory');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->isWritable('//./..///../Directory');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->isWritable('\..\..\Directory');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(3, $exceptions);
    }

    /**
     * Test for openFile method
     *
     * @dataProvider openFileProvider
     * @param string $basePath
     * @param int $permissions
     * @param string $path
     * @param string $mode
     */
    public function testOpenFile($basePath, $permissions, $path, $mode)
    {
        $directory = $this->getDirectoryInstance($basePath, $permissions);
        $file = $directory->openFile($path, $mode);
        $this->assertTrue($file instanceof \Magento\Framework\Filesystem\File\WriteInterface);
        $file->close();
    }

    /**
     * Data provider for testOpenFile
     *
     * @return array
     */
    public function openFileProvider()
    {
        return [
            ['newDir1', 0777, 'newFile.txt', 'w+'],
            ['newDir1', 0777, 'subdirectory/newFile.txt', 'w+']
        ];
    }

    public function testOpenFileOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        try {
            $dir->openFile('../../Directory/ReadTest.php');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->openFile('//./..///../Directory/ReadTest.php');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->openFile('\..\..\Directory\ReadTest.php');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(3, $exceptions);
    }

    /**
     * Test writeFile
     *
     * @dataProvider writeFileProvider
     * @param string $path
     * @param string $content
     * @param string $extraContent
     */
    public function testWriteFile($path, $content, $extraContent)
    {
        $directory = $this->getDirectoryInstance('writeFileDir', 0777);
        $directory->writeFile($path, $content);
        $this->assertEquals($content, $directory->readFile($path));
        $directory->writeFile($path, $extraContent);
        $this->assertEquals($extraContent, $directory->readFile($path));
    }

    /**
     * Test writeFile for append mode
     *
     * @dataProvider writeFileProvider
     * @param string $path
     * @param string $content
     * @param string $extraContent
     */
    public function testWriteFileAppend($path, $content, $extraContent)
    {
        $directory = $this->getDirectoryInstance('writeFileDir', 0777);
        $directory->writeFile($path, $content, 'a+');
        $this->assertEquals($content, $directory->readFile($path));
        $directory->writeFile($path, $extraContent, 'a+');
        $this->assertEquals($content . $extraContent, $directory->readFile($path));
    }

    /**
     * Data provider for testWriteFile and testWriteFileAppend
     *
     * @return array
     */
    public function writeFileProvider()
    {
        return [['file1', '123', '456'], ['folder1/file1', '123', '456']];
    }

    public function testWriteFileOutside()
    {
        $exceptions = 0;
        $dir = $this->getDirectoryInstance('newDir1', 0777);
        try {
            $dir->writeFile('../../Directory/ReadTest.php', 'tst');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->writeFile('//./..///../Directory/ReadTest.php', 'tst');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        try {
            $dir->writeFile('\..\..\Directory\ReadTest.php', 'tst');
        } catch (ValidatorException $exception) {
            $exceptions++;
        }
        $this->assertEquals(3, $exceptions);
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
        /** @var Write $directory */
        foreach ($this->testDirectories as $directory) {
            if ($directory->isExist()) {
                $directory->delete();
            }
        }
    }

    /**
     * Get readable file instance
     * Get full path for files located in _files directory
     *
     * @param string $path
     * @param string $permissions
     * @return Write
     */
    private function getDirectoryInstance($path, $permissions)
    {
        $fullPath = __DIR__ . '/../_files/' . $path;
        $objectManager = Bootstrap::getObjectManager();
        /** @var \Magento\Framework\Filesystem\Directory\WriteFactory $directoryFactory */
        $directoryFactory = $objectManager->create(\Magento\Framework\Filesystem\Directory\WriteFactory::class);
        $directory = $directoryFactory->create($fullPath, DriverPool::FILE, $permissions);
        $this->testDirectories[] = $directory;
        return $directory;
    }
}
