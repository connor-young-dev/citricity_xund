<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace theme_citricityxund\cli;

use stdClass;

/**
 * Routine to populate courses with category-based overview images.
 *
 * @package   theme_citricityxund
 * @author    Guy Thomas
 * @copyright 2022 Citricity Ltd <http://citr.city> / FFHS MediaFactory
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class populate_course_images {
    /** @var string[] A flat array of all the default image names. */
    private $defaultimages = [];

    /** @var array Image file paths keyed by category idnumber, then by filename. */
    private $catimagesbycatidnumber = [];

    /** @var array Image usage counts keyed by category idnumber then filename (to find the least-used image). */
    private $catimagecounts = [];

    /** @var array Category idnumbers keyed by category id (inherited down to sub-categories without their own idnumber). */
    private $catidnumbersbyid = [];

    /** @var string|null Absolute path to the directory holding the per-category image folders. */
    private $imgdir = null;

    /**
     * Constructor.
     *
     * @param string|null $imgdir Path to the category images directory (defaults to the theme assets folder).
     * @param bool $reset Whether to wipe all existing course overview images first.
     */
    private function __construct($imgdir = null, $reset = false) {
        global $CFG;

        $this->imgdir = $imgdir ?? $CFG->dirroot . '/theme/citricityxund/assets/categoryimages';
        $this->init($reset);
    }

    /**
     * Build the internal lookups (and optionally wipe existing course images first).
     *
     * @param bool $reset Whether to wipe all existing course overview images first.
     */
    private function init($reset = false): void {
        if ($reset) {
            $this->wipe_out_all_course_images();
        }
        $this->set_catidnumbersbyid();
        $this->set_catimagesbycatidnumber();
        $this->set_category_course_image_counts();
    }

    /**
     * Delete the overview image files from every course.
     */
    private function wipe_out_all_course_images(): void {
        $courses = get_courses();
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'course', 'overviewfiles');
        }
    }

    /**
     * Build an instance pointing at the test fixtures (PHPUnit only).
     *
     * @return populate_course_images
     */
    public static function get_test_instance(): populate_course_images {
        global $CFG;

        if (!PHPUNIT_TEST) {
            throw new \coding_exception('You may only use this method from within a php unit test');
        }
        $imgdir = $CFG->dirroot . '/theme/citricityxund/tests/fixtures/categoryimages';
        return new populate_course_images($imgdir);
    }

    /**
     * Build the map of available category images from the asset folders (one folder per category idnumber).
     */
    private function set_catimagesbycatidnumber(): void {
        $dir = new \DirectoryIterator($this->imgdir);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }
            if (!$fileinfo->isDir()) {
                continue;
            }

            // This is the folder name that should correspond to the category idnumber.
            $catidnumber = $fileinfo->getFilename();
            $catdir = new \DirectoryIterator($fileinfo->getPath() . '/' . $catidnumber);
            foreach ($catdir as $imagefileinfo) {
                if ($imagefileinfo->isDot()) {
                    continue;
                }
                if (!isset($this->catimagesbycatidnumber[$catidnumber])) {
                    $this->catimagesbycatidnumber[$catidnumber] = [];
                }

                $imgexts = ['png', 'jpg', 'jpeg', 'svg', 'gif'];
                $ext = strtolower($imagefileinfo->getExtension());
                if (!in_array($ext, $imgexts)) {
                    // Not an image.
                    continue;
                }

                $this->defaultimages[] = $imagefileinfo->getFilename();

                $this->catimagesbycatidnumber[$catidnumber][$imagefileinfo->getFilename()] =
                    $imagefileinfo->getPath() . '/' . $imagefileinfo->getFilename();
            }
        }
    }

    /**
     * Find the nearest ancestor category that has an idnumber.
     *
     * @param stdClass $catrow The category record (with a path).
     * @param array $catsbypath Category records keyed by their path.
     * @return stdClass|null The nearest ancestor with an idnumber, or null if none.
     */
    private function get_ancestor_with_idnumber(stdClass $catrow, array $catsbypath): ?stdClass {
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

    /**
     * Return the course's overview image file, if it has a valid one.
     *
     * @param int $courseid The course id.
     * @return \stored_file|null The overview image file, or null if none.
     */
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

    /**
     * Store an overview image on a course from a file on disk.
     *
     * @param int $courseid The course id.
     * @param string $filepath Absolute path to the source image file.
     */
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

    /**
     * Fetch a category record by idnumber (statically cached).
     *
     * @param string $idnumber The category idnumber.
     * @return stdClass|null The category record, or null if not found.
     */
    private function get_course_category_by_idnumber(string $idnumber): ?stdClass {
        global $DB;

        static $categories = [];

        // Performance - cache category record so only retrieved once.
        if (isset($categories[$idnumber])) {
            return $categories[$idnumber];
        }
        $categories = $DB->get_records('course_categories', ['idnumber' => $idnumber]);
        if (count($categories) > 1) {
            throw new \coding_exception('Attempt to get category by idnumber "' . $idnumber . '" returned more than one category');
        }
        $category = count($categories) === 1 ? reset($categories) : null;
        $categories[$idnumber] = $category;

        return $categories[$idnumber];
    }

    /**
     * Fetch a category record by id (statically cached).
     *
     * @param int $categoryid The category id.
     * @return stdClass|null The category record, or null if not found.
     */
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

    /**
     * Print a section title to the CLI trace output.
     *
     * @param string $title The title to print.
     */
    private function trace_section_title(string $title): void {
        mtrace("\n");
        mtrace(str_repeat('-', 30));
        mtrace($title);
        mtrace(str_repeat('-', 30));
    }

    /**
     * Count how many times each category image is currently used across courses.
     */
    private function set_category_course_image_counts(): void {
        $catimgcounts = []; // Image filename counts hashed by categories / image name.
        $courses = get_courses();
        $c = 0;
        $coursecount = count($courses);
        $this->trace_section_title('Checking course image usage throughout categories');

        // Initialise counts.
        foreach ($this->catimagesbycatidnumber as $catidnumber => $images) {
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
            $c++;
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

            if (!isset($this->catimagesbycatidnumber[$categoryidnumber])) {
                mtrace("Unsupported category - no asset folder corresponds to $categoryidnumber");
                continue;
            }

            $courseimage = $this->get_course_image($course->id);
            if ($courseimage) {
                $imagefilename = $courseimage->get_filename();
                if (!in_array($imagefilename, $this->defaultimages)) {
                    // Skip this image since it is not a default image present in assets/categoryimages.
                    continue;
                }

                if (!isset($catimgcounts[$categoryidnumber])) {
                    $catimgcounts[$categoryidnumber] = [];
                    foreach ($this->catimagesbycatidnumber[$categoryidnumber] as $catimagefilename => $filepath) {
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

    /**
     * Return the least-used image filename for a category idnumber.
     *
     * @param string $idnumber The category idnumber.
     * @return string|null The least-used image filename, or null if the category has no images.
     */
    private function get_least_used_image_for_categoryidnumber(string $idnumber): ?string {
        $lowestcount = 0;
        $leastusedimg = null;
        if (!isset($this->catimagecounts[$idnumber])) {
            $category = $this->get_course_category_by_idnumber($idnumber);
            mtrace('Invalid category: ' . $category->name);
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

    /**
     * Assign the least-used category image to each course that lacks an overview image.
     */
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

            if (!isset($this->catimagesbycatidnumber[$categoryidnumber][$leastusedimage])) {
                // This is most likely a custom image uploaded via a user, so it won't exist in catimagesbycatidnumber.
                continue;
            }
            $path = $this->catimagesbycatidnumber[$categoryidnumber][$leastusedimage];

            // Add image to course.
            $this->set_course_image_from_filepath($course->id, $path);

            // Update counts.
            $this->catimagecounts[$categoryidnumber][$leastusedimage]++;
        }
    }

    /**
     * Entry point: populate overview images across all courses on the site.
     */
    public static function do() {
        static $me = null;
        if (!$me) {
            $me = new populate_course_images();
        }
        $me->process_courses();
    }
}
