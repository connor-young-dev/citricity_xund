<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for category image population
 *
 * @package    theme_citricityxund
 * @category   test
 * @group      wip
 * @group      theme_citricityxund
 * @copyright  2022 Guy Thomas dev@citri.city
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_citricityxund;

defined('MOODLE_INTERNAL') || die();

require(__DIR__.'/util.php');

use theme_citricityxund\cli\populate_course_images;
use theme_citricityxund\util;

/**
 * Tests for category image population
 *
 * @package    theme_citricityxund
 * @category   test
 * @group      wip
 * @group      theme_citricityxund
 * @copyright  2022 Guy Thomas dev@citri.city
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class populate_course_images_test extends \advanced_testcase {

    public function test_set_catimagesbycatidnumber() {
        ob_start();
        $instance = \theme_citricityxund\cli\populate_course_images::get_test_instance();
        \phpunit_util::call_internal_method($instance, 'set_catimagesbycatidnumber', [], get_class($instance));
        $output = ob_get_clean();
        $catimages = util::get_restricted_property_value($instance, 'catimagesbycatidnumber');
        $this->assertStringContainsString('Checking course image usage throughout categories', $output);
        $this->assertNotEmpty($catimages);
        $this->assertEquals(3, count($catimages[5]));
    }

    public function test_set_catidnumbersbyid_no_idnumbers() {
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $dg->create_category();
        $dg->create_category();
        $dg->create_category();
        ob_start();
        $instance = \theme_citricityxund\cli\populate_course_images::get_test_instance();
        ob_get_clean();
        $catidnumbersbyid = util::get_restricted_property_value($instance, 'catidnumbersbyid');
        $this->assertEmpty($catidnumbersbyid);
    }

    public function test_set_catidnumbersbyid_with_idnumbers() {
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $cat1 = $dg->create_category(['idnumber' => 'cat1']);
        $cat2 = $dg->create_category(['idnumber' => 'cat2']);
        $cat3 = $dg->create_category();
        ob_start();
        $instance = \theme_citricityxund\cli\populate_course_images::get_test_instance();
        ob_get_clean();
        $catidnumbersbyid = util::get_restricted_property_value($instance, 'catidnumbersbyid');
        $this->assertNotEmpty($catidnumbersbyid);
        $this->assertArrayHasKey($cat1->id, $catidnumbersbyid);
        $this->assertArrayHasKey($cat2->id, $catidnumbersbyid);
        $this->assertArrayNotHasKey($cat3->id, $catidnumbersbyid);
        $this->assertNotEmpty($catidnumbersbyid[$cat1->id]);
        $this->assertNotEmpty($catidnumbersbyid[$cat2->id]);
    }

    public function test_set_catidnumbersbyid_with_idnumbers_subcategories() {
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $cat1 = $dg->create_category(['idnumber' => 'cat1']);
        $subcat1 = $dg->create_category(['parent' => $cat1->id]);
        $cat2 = $dg->create_category(['idnumber' => 'cat2']);
        $subcat21 = $dg->create_category(['parent' => $cat2->id]);
        $subcat22 = $dg->create_category(['parent' => $cat2->id]);

        // Create a sub cat that has its own idnumber.
        $subcat23 = $dg->create_category(['parent' => $cat2->id, 'idnumber' => 'subcat23']);
        $subcat231 = $dg->create_category(['parent' => $subcat23->id]);
        $cat3 = $dg->create_category();
        ob_start();
        $instance = \theme_citricityxund\cli\populate_course_images::get_test_instance();
        ob_get_clean();
        $catidnumbersbyid = util::get_restricted_property_value($instance, 'catidnumbersbyid');
        $this->assertNotEmpty($catidnumbersbyid);
        $this->assertArrayHasKey($cat1->id, $catidnumbersbyid);
        $this->assertArrayHasKey($subcat1->id, $catidnumbersbyid);
        $this->assertArrayHasKey($cat2->id, $catidnumbersbyid);
        $this->assertArrayHasKey($subcat21->id, $catidnumbersbyid);
        $this->assertArrayHasKey($subcat22->id, $catidnumbersbyid);
        $this->assertArrayHasKey($subcat23->id, $catidnumbersbyid);
        $this->assertArrayHasKey($subcat231->id, $catidnumbersbyid);
        $this->assertArrayNotHasKey($cat3->id, $catidnumbersbyid);
        $this->assertNotEmpty($catidnumbersbyid[$cat1->id]);
        $this->assertNotEmpty($catidnumbersbyid[$cat2->id]);
        $this->assertNotEmpty($catidnumbersbyid[$subcat1->id]);
        $this->assertNotEmpty($catidnumbersbyid[$subcat21->id]);
        $this->assertNotEmpty($catidnumbersbyid[$subcat22->id]);
        $this->assertNotEmpty($catidnumbersbyid[$subcat23->id]);
        $this->assertNotEmpty($catidnumbersbyid[$subcat231->id]);

        // Ensure sub categories have inherited the correct idnumber.
        $this->assertEquals($cat1->idnumber, $catidnumbersbyid[$subcat1->id]);
        $this->assertEquals($cat2->idnumber, $catidnumbersbyid[$subcat21->id]);
        $this->assertEquals($cat2->idnumber, $catidnumbersbyid[$subcat22->id]);
        // Here we are testing sub categories of sub categories.
        $this->assertEquals($subcat23->idnumber, $catidnumbersbyid[$subcat23->id]);
        $this->assertEquals($subcat23->idnumber, $catidnumbersbyid[$subcat231->id]);
    }

    public function test_set_category_course_image_counts() {
        global $CFG;

        $this->resetAfterTest();

        // Check without courses returns 0 for each categoryid.
        // Note - set_category_course_image_counts is called on instantiation.
        ob_start();
        $instance = \theme_citricityxund\cli\populate_course_images::get_test_instance();
        $output = ob_get_clean();
        $imagecounts = util::get_restricted_property_value($instance, 'catimagecounts');
        $this->assertStringContainsString('Checking course image usage throughout categories', $output);
        $this->assertEmpty($imagecounts);

        // Check with categories.
        $dg = $this->getDataGenerator();
        $catidnumber5 = $dg->create_category(['idnumber' => '5']);
        $catidnumber127 = $dg->create_category(['idnumber' => '127']);
        $catidnumber141 = $dg->create_category(['idnumber' => '141']);

        // Reinitialize (calls set_category_course_image_counts).
        $this->call_private_method($instance, 'init', []);

        $imagecounts = util::get_restricted_property_value($instance, 'catimagecounts');
        $this->assertEquals(0, $imagecounts[5]['Xund_Icon_Wegweiser_1.png']);
        $this->assertEquals(0, $imagecounts[5]['Xund_Icon_Wegweiser_2.png']);
        $this->assertEquals(0, $imagecounts[5]['Xund_Icon_Wegweiser_3.png']);
        $this->assertEquals(0, $imagecounts[127]['Xund_Icon_Mikroskop_1.png']);
        $this->assertEquals(0, $imagecounts[127]['Xund_Icon_Mikroskop_2.png']);
        $this->assertEquals(0, $imagecounts[127]['Xund_Icon_Mikroskop_3.png']);
        $this->assertEquals(0, $imagecounts[141]['Xund_Icons_Bett_1.png']);
        $this->assertEquals(0, $imagecounts[141]['Xund_Icons_Bett_2.png']);
        $this->assertEquals(0, $imagecounts[141]['Xund_Icons_Bett_3.png']);
        // We never created a category with the idnumber 463 so this should not exist in the counts.
        $this->assertArrayNotHasKey('463', $imagecounts);

        // Check with courses + images in categories.
        $course5n1 = $dg->create_course(['category' => $catidnumber5->id]);
        $filepath = $CFG->dirroot.'/theme/citricityxund/tests/fixtures/categoryimages/5/Xund_Icon_Wegweiser_1.png';
        $this->set_course_image_from_filepath($instance, $course5n1->id, $filepath);

        // Reinitialize (calls set_category_course_image_counts).
        $this->call_private_method($instance, 'init', []);
        $imagecounts = util::get_restricted_property_value($instance, 'catimagecounts');
        $this->assertEquals(1, $imagecounts[5]['Xund_Icon_Wegweiser_1.png']);
        $this->assertEquals(0, $imagecounts[5]['Xund_Icon_Wegweiser_2.png']);
        $this->assertEquals(0, $imagecounts[5]['Xund_Icon_Wegweiser_3.png']);
        $course5n2 = $dg->create_course(['category' => $catidnumber5->id]);
        $filepath = $CFG->dirroot.'/theme/citricityxund/tests/fixtures/categoryimages/5/Xund_Icon_Wegweiser_2.png';
        $this->set_course_image_from_filepath($instance, $course5n2->id, $filepath);
        $course5n3 = $dg->create_course(['category' => $catidnumber5->id]);
        $filepath = $CFG->dirroot.'/theme/citricityxund/tests/fixtures/categoryimages/5/Xund_Icon_Wegweiser_3.png';
        $this->set_course_image_from_filepath($instance, $course5n3->id, $filepath);
        $course5n4 = $dg->create_course(['category' => $catidnumber5->id]);
        $filepath = $CFG->dirroot.'/theme/citricityxund/tests/fixtures/categoryimages/5/Xund_Icon_Wegweiser_1.png';
        $this->set_course_image_from_filepath($instance, $course5n4->id, $filepath);

        // Reinitialize (calls set_category_course_image_counts).
        $this->call_private_method($instance, 'init', []);
        $imagecounts = util::get_restricted_property_value($instance, 'catimagecounts');
        $this->assertEquals(2, $imagecounts[5]['Xund_Icon_Wegweiser_1.png']);
        $this->assertEquals(1, $imagecounts[5]['Xund_Icon_Wegweiser_2.png']);
        $this->assertEquals(1, $imagecounts[5]['Xund_Icon_Wegweiser_3.png']);

        [, $leastused] = $this->call_private_method($instance, 'get_least_used_image_for_categoryidnumber', ['5']);
        $this->assertEquals('Xund_Icon_Wegweiser_2.png', $leastused);
    }

    private function set_course_image_from_filepath(populate_course_images $instance, int $courseid, string $filepath) {
        \phpunit_util::call_internal_method($instance, 'set_course_image_from_filepath',
            [$courseid, $filepath], get_class($instance));
    }

    private function call_private_method(populate_course_images $instance, string $method, array $params = [],
        $silent = true) {
        ob_start();
        $methodresult = \phpunit_util::call_internal_method($instance, $method, $params, get_class($instance));
        $clioutput = ob_get_clean();
        if (!$silent) {
            echo $clioutput;
        }
        return [$clioutput, $methodresult];
    }
}
