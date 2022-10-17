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

    public function test_set_cat_images() {
        ob_start();
        $instance = \theme_citricityxund\cli\populate_course_images::get_test_instance();
        \phpunit_util::call_internal_method($instance, 'set_cat_images', [], get_class($instance));
        $output = ob_get_clean();
        $catimages = util::get_restricted_property_value($instance, 'catimages');
        $this->assertStringContainsString('Checking course image usage throughout categories', $output);
        $this->assertNotEmpty($catimages);
        $this->assertEquals(3, count($catimages[5]));
    }

    public function test_set_category_course_image_counts() {
        global $CFG;

        $this->resetAfterTest();
        // Check without courses returns 0 for each categoryid.
        ob_start();
        // Note - set_category_course_image_counts is called on instantiation.
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

        ob_start();
        \phpunit_util::call_internal_method($instance, 'set_category_course_image_counts', [], get_class($instance));
        ob_get_clean();
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
        $filepath = $CFG->dirroot.'/theme/citricityxund/assets/categoryimages/5/Xund_Icon_Wegweiser_1.png';
        $this->set_course_image_from_filepath($instance, $course5n1->id, $filepath);
        ob_start();
        \phpunit_util::call_internal_method($instance, 'set_category_course_image_counts', [], get_class($instance));
        ob_get_clean();
        $imagecounts = util::get_restricted_property_value($instance, 'catimagecounts');
        $this->assertEquals(1, $imagecounts[5]['Xund_Icon_Wegweiser_1.png']);
        $this->assertEquals(0, $imagecounts[5]['Xund_Icon_Wegweiser_2.png']);
        $this->assertEquals(0, $imagecounts[5]['Xund_Icon_Wegweiser_3.png']);
        $course5n2 = $dg->create_course(['category' => $catidnumber5->id]);
        $filepath = $CFG->dirroot.'/theme/citricityxund/assets/categoryimages/5/Xund_Icon_Wegweiser_2.png';
        $this->set_course_image_from_filepath($instance, $course5n2->id, $filepath);
        $course5n3 = $dg->create_course(['category' => $catidnumber5->id]);
        $filepath = $CFG->dirroot.'/theme/citricityxund/assets/categoryimages/5/Xund_Icon_Wegweiser_3.png';
        $this->set_course_image_from_filepath($instance, $course5n3->id, $filepath);
        $course5n4 = $dg->create_course(['category' => $catidnumber5->id]);
        $filepath = $CFG->dirroot.'/theme/citricityxund/assets/categoryimages/5/Xund_Icon_Wegweiser_1.png';
        $this->set_course_image_from_filepath($instance, $course5n4->id, $filepath);
        ob_start();
        \phpunit_util::call_internal_method($instance, 'set_category_course_image_counts', [], get_class($instance));
        ob_get_clean();
        $imagecounts = util::get_restricted_property_value($instance, 'catimagecounts');
        $this->assertEquals(2, $imagecounts[5]['Xund_Icon_Wegweiser_1.png']);
        $this->assertEquals(1, $imagecounts[5]['Xund_Icon_Wegweiser_2.png']);
        $this->assertEquals(1, $imagecounts[5]['Xund_Icon_Wegweiser_3.png']);

        $leastused = \phpunit_util::call_internal_method($instance, 'get_least_used_image_for_categoryidnumber', ['5'], get_class($instance));
        $this->assertEquals('Xund_Icon_Wegweiser_2.png', $leastused);
    }

    private function set_course_image_from_filepath(populate_course_images $instance, int $courseid, string $filepath) {
        \phpunit_util::call_internal_method($instance, 'set_course_image_from_filepath',
            [$courseid, $filepath], get_class($instance));
    }
}
