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

namespace theme_citricityxund\cli;

use stdClass;

/**
 * Cli routine to populate course images.
 *
 * @package    theme_citricityxund
 * @copyright  2022 Citricity http://citri.city
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class populate_course_images {
    private $catimages = [];
    private $catimagecounts = [];
    // Category idnumbers hashed by id - note: carries the idnumber down to sub categories that don't have idnumbers.
    private $catidnumbersbyid = [];
    private $imgdir = null;

    private function __construct($imgdir = null, $reset = false) {
        global $CFG;

        $this->imgdir = $imgdir ?? $CFG->dirroot.'/theme/citricityxund/assets/categoryimages';
        $this->init($reset);
    }

    private function init($reset = false): void {
        if ($reset) {
            $this->wipe_out_all_course_images();
        }
        $this->set_catidnumbersbyid();
        $this->set_cat_images();
        $this->set_category_course_image_counts();
    }

    private function wipe_out_all_course_images(): void {
        $courses = get_courses();
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'course', 'overviewfiles');
        }
    }

    public static function get_test_instance(): populate_course_images {
        global $CFG;

        if (!PHPUNIT_TEST) {
            throw new \coding_exception('You may only use this method from within a php unit test');
        }
        $imgdir = $CFG->dirroot.'/theme/citricityxund/tests/fixtures/categoryimages';
        return new populate_course_images($imgdir);
    }

    private function set_cat_images(): void {
        $dir = new \DirectoryIterator($this->imgdir);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }
            if ($fileinfo->isDir()) {
                // This is the folder name that should correspond to the category idnumber.
                $catidnumber = $fileinfo->getFilename();
                $catdir = new \DirectoryIterator($fileinfo->getPath().'/'.$catidnumber);
                foreach ($catdir as $imagefileinfo) {
                    if ($imagefileinfo->isDot()) {
                        continue;
                    }
                    if (!isset($this->catimages[$catidnumber])) {
                        $this->catimages[$catidnumber] = [];
                    }

                    $imgexts = ['png', 'jpg', 'jpeg', 'svg', 'gif'];
                    $ext = strtolower($imagefileinfo->getExtension());
                    if (!in_array($ext, $imgexts)) {
                        // Not an image.
                        continue;
                    }

                    $this->catimages[$catidnumber][$imagefileinfo->getFilename()] =
                        $imagefileinfo->getPath().'/'.$imagefileinfo->getFilename();
                }
            }
        }
    }

    private function get_ancestor_with_idnumber(stdClass $catrow, array $catsbypath) :?stdClass {
        $parts = explode('/', $catrow->path);
        array_pop($parts);
        $parentpath = implode('/', $parts);
        if (empty($parentpath)) {
            return null;
        }
        $parent = $catsbypath[$parentpath];
        if (!empty($parent->idnumber)) {
            return $parent;
        }
        return $this->get_ancestor_with_idnumber($parent, $catsbypath);
    }

    /**
     * Set category idnumbers hashed by id - carries the idnumber down to sub categories that don't have idnumbers.
     * Note - looked at using core_course_category::get_all() but we don't want to treat invisible categories differently.
     */
    private function set_catidnumbersbyid(): void {
        global $DB;
        $sql = "SELECT id, parent, idnumber, path
                FROM {course_categories} 
                ORDER BY sortorder";
        $catidnumbers = [];
        $catsbypath = [];
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $row) {
            $catsbypath[$row->path] = $row;
        }
        $rs->close();

        foreach ($catsbypath as $row) {
            if (!empty($row->idnumber)) {
                $catidnumbers[$row->id] = $row->idnumber;
            } else {
                $parent = $this->get_ancestor_with_idnumber($row, $catsbypath);
                if ($parent) {
                    $catidnumbers[$row->id] = $parent->idnumber;
                }
            }
        }
        $this->catidnumbersbyid = $catidnumbers;
    }

    private function get_course_image(int $courseid): ?\stored_file {
        $context = \context_course::instance($courseid);
        $fs = get_file_storage();

        if ($context->contextlevel === CONTEXT_SYSTEM) {
            return null;
        }

        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);
        if (count($files)) {
            foreach ($files as $file) {
                $isimage = $file->is_valid_image();
                if ($isimage) {
                    return $file;
                }
            }
        }
        return null;
    }

    private function set_course_image_from_filepath(int $courseid, string $filepath): void {
        $context = \context_course::instance($courseid);
        $fs = get_file_storage();

        $filerecord = [
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => basename($filepath),
        ];
        $fs->create_file_from_pathname($filerecord, $filepath);
    }

    private function get_course_category_by_idnumber(string $idnumber): ?stdClass {
        global $DB;

        static $categories = [];

        // Performance - cache category record so only retrieved once.
        if (isset($categories[$idnumber])) {
            return $categories[$idnumber];
        }
        $categories = $DB->get_records('course_categories', ['idnumber' => $idnumber]);
        if (count($categories) > 1) {
            throw new \coding_exception('Attempt to get category by idnumber "'.$idnumber.'" returned more than one category');
        }
        $category = count($categories) === 1 ? reset($categories) : null;
        $categories[$idnumber] = $category;

        return $categories[$idnumber];
    }

    private function get_course_category(int $categoryid): ?stdClass {
        global $DB;

        static $categories = [];

        // Performance - cache category record so only retrieved once.
        if (isset($categories[$categoryid])) {
            return $categories[$categoryid];
        }
        $category = $DB->get_record('course_categories', ['id' => $categoryid]);
        if (!$category) {
            $categories[$categoryid] = null;
        } else {
            $categories[$categoryid] = $category;
        }
        return $categories[$categoryid];
    }

    private function trace_section_title(string $title): void {
        mtrace("\n");
        mtrace(str_repeat('-', 30));
        mtrace($title);
        mtrace(str_repeat('-', 30));
    }

    private function set_category_course_image_counts(): void {
        $catimgcounts = []; // Image filename counts hashed by categories / image name.
        $courses = get_courses();
        $c = 0;
        $coursecount = count($courses);
        $this->trace_section_title('Checking course image usage throughout categories');

        // Initialise counts.
        foreach ($this->catimages as $catidnumber => $images) {
            $category = $this->get_course_category_by_idnumber($catidnumber);
            if (empty($category)) {
                // Skip if category does not exist.
                continue;
            }
            foreach ($images as $imagefile => $imagepath) {
                if (!isset($catimgcounts[$catidnumber][$imagefile])) {
                    $catimgcounts[$catidnumber][$imagefile] = 0;
                }
            }
        }

        foreach ($courses as $course) {
            $c ++;
            mtrace("Checking course image $c of $coursecount ($course->shortname)");
            if ($course->id === SITEID) {
                mtrace("Skipping site course");
                continue;
            }

            $category = $this->get_course_category($course->category);

            // Get category for course.
            $categoryidnumber = $this->catidnumbersbyid[intval($course->category)] ?? null;
            if (empty($categoryidnumber)) {
                mtrace("Skipping category as it has no idnumber ($category->name)");
                continue;
            }

            if (!isset($this->catimages[$categoryidnumber])) {
                mtrace("Unsupported category - no asset folder corresponds to $categoryidnumber");
                continue;
            }

            $courseimage = $this->get_course_image($course->id);
            if ($courseimage) {
                $imagefilename = $courseimage->get_filename();

                if (!isset($catimgcounts[$categoryidnumber])) {
                    $catimgcounts[$categoryidnumber] = [];
                    foreach ($this->catimages[$categoryidnumber] as $catimagefilename => $filepath) {
                        $catimgcounts[$categoryidnumber][$catimagefilename] = 0;
                    }
                }
                if (!isset($catimgcounts[$categoryidnumber][$imagefilename])) {
                    $catimgcounts[$categoryidnumber][$imagefilename] = 0;
                }
                $catimgcounts[$categoryidnumber][$imagefilename]++;
            }
        }
        $this->catimagecounts = $catimgcounts;
    }

    private function get_least_used_image_for_categoryidnumber(string $idnumber): ?string {
        $lowestcount = 0;
        $leastusedimg = null;
        if (!isset($this->catimagecounts[$idnumber])) {
            $category = $this->get_course_category_by_idnumber($idnumber);
            mtrace('Invalid category: '.$category->name);
            return null;
        }

        foreach ($this->catimagecounts[$idnumber] as $catimage => $count) {
            if ($count < $lowestcount || $leastusedimg === null) {
                $lowestcount = $count;
                $leastusedimg = $catimage;
            }
        }
        return $leastusedimg;
    }

    private function process_courses() {
        $courses = get_courses();
        $c = 0;
        $coursecount = count($courses);
        $this->trace_section_title('Processing courses');
        foreach ($courses as $course) {
            $c++;
            mtrace("Processing course $c of $coursecount ($course->shortname)");
            if ($course->id === SITEID) {
                mtrace("Skipping site course");
                continue;
            }
            $category = $this->get_course_category($course->category);
            $categoryidnumber = $this->catidnumbersbyid[intval($course->category)] ?? null;
            if (empty($categoryidnumber)) {
                mtrace("Skipping category without idnumber - $category->name");
                continue;
            }
            $leastusedimage = $this->get_least_used_image_for_categoryidnumber($categoryidnumber);
            if (empty($leastusedimage)) {
                mtrace("Course category does not appear to have images (\"{$category->name}\" - idnumber: $categoryidnumber)");
                continue;
            }
            $courseimage = $this->get_course_image($course->id);
            if ($courseimage) {
                mtrace("Course already has an image $course->shortname");
                continue;
            }
            mtrace("Adding course image $leastusedimage to course ($course->shortname)");

            if (!isset($this->catimages[$categoryidnumber][$leastusedimage])) {
                throw new \coding_exception('Failed to get path for image '.$leastusedimage.' in category '.$categoryidnumber);
            }
            $path = $this->catimages[$categoryidnumber][$leastusedimage];

            // Add image to course.
            $this->set_course_image_from_filepath($course->id, $path);

            // Update counts.
            $this->catimagecounts[$categoryidnumber][$leastusedimage]++;
        }
    }

    public static function do() {
        static $me = null;
        if (!$me) {
            $me = new populate_course_images();
        }
        $me->process_courses();
    }
}
