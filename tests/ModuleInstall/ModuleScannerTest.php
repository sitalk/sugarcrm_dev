<?php
/*********************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2012 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

require_once 'ModuleInstall/ModuleScanner.php';

class ModuleScannerTest extends Sugar_PHPUnit_Framework_TestCase
{
    var $fileLoc;

	public function setUp()
	{
        $this->fileLoc = "cache/moduleScannerTemp.php";
	}

	public function tearDown()
	{
		if (is_file($this->fileLoc))
			unlink($this->fileLoc);
	}

	public function phpSamples()
	{
	    return array(
	        array("<?php echo blah;", true),
	        array("<? echo blah;", true),
	        array("blah <? echo blah;", true),
	        array("blah <?xml echo blah;", true),
	        array("<?xml version=\"1.0\"></xml>", false),
	        array("<?xml \n echo blah;", true),
	        array("<?xml version=\"1.0\"><? blah ?></xml>", true),
	        array("<?xml version=\"1.0\"><?php blah ?></xml>", true),
	        );
	}

	/**
	 * @dataProvider phpSamples
	 */
	public function testPHPFile($content, $is_php)
	{
        $ms = new MockModuleScanner();
	    $this->assertEquals($is_php, $ms->isPHPFile($content), "Bad PHP file result");
	}

	public function testFileTemplatePass()
    {

    	$fileModContents = <<<EOQ
<?PHP
require_once('include/SugarObjects/templates/file/File.php');

class testFile_sugar extends File {
	function fileT_testFiles_sugar(){
		parent::File();
		\$this->file = new File();
		\$file = "file";
	}
}
?>
EOQ;
		file_put_contents($this->fileLoc, $fileModContents);
		$ms = new ModuleScanner();
		$errors = $ms->scanFile($this->fileLoc);
		$this->assertTrue(empty($errors));
    }

	public function testFileFunctionFail()
    {

    	$fileModContents = <<<EOQ
<?PHP
require_once('include/SugarObjects/templates/file/File.php');

class testFile_sugar extends File {
	function fileT_testFiles_sugar(){
		parent::File();
		\$this->file = new File();
		\$file = file('test.php');

	}
}
?>
EOQ;
		file_put_contents($this->fileLoc, $fileModContents);
		$ms = new ModuleScanner();
		$errors = $ms->scanFile($this->fileLoc);
		$this->assertTrue(!empty($errors));
    }

	public function testCallUserFunctionFail()
    {

    	$fileModContents = <<<EOQ
<?PHP
	call_user_func("sugar_file_put_contents", "test2.php", "test");
?>
EOQ;
		file_put_contents($this->fileLoc, $fileModContents);
		$ms = new ModuleScanner();
		$errors = $ms->scanFile($this->fileLoc);
		$this->assertTrue(!empty($errors));
    }

    /**
     * @group bug58072
     */
	public function testLockConfig()
    {

    	$fileModContents = <<<EOQ
<?PHP
	\$GLOBALS['sugar_config']['moduleInstaller']['test'] = true;
    	\$manifest = array();
    	\$installdefs = array();
?>
EOQ;
		file_put_contents($this->fileLoc, $fileModContents);
		$ms = new MockModuleScanner();
		$ms->config['test'] = false;
		$ms->lockConfig();
		MSLoadManifest($this->fileLoc);
		$errors = $ms->checkConfig($this->fileLoc);
		$this->assertTrue(!empty($errors), "Not detected config change");
		$this->assertFalse($ms->config['test'], "config was changed");
    }
}

class MockModuleScanner extends  ModuleScanner
{
    public $config;
    public function isPHPFile($contents) {
        return parent::isPHPFile($contents);
    }
}

