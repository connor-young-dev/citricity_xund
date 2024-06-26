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

namespace theme_citricityxund;

use moodle_url;

/**
 * Creates a navbar for citricity xund theme.
 *
 * This class is copied and modified from /theme/boost/classes/boostnavbar.php
 *
 * @package    theme_citricityxund
 * @copyright  based on code from theme_boost by Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class boostnavbar extends \theme_boost\boostnavbar {

    /**
     * Prepares the navigation nodes for use with boost.
     *
     */
    protected function prepare_nodes_for_boost(): void {
        global $PAGE;

        // Remove the navbar nodes that already exist in the primary navigation menu.
        $this->remove_items_that_exist_in_navigation($PAGE->primarynav);

        // Defines whether section items with an action should be removed by default.
        $removesections = true;

        if ($this->page->context->contextlevel == CONTEXT_COURSECAT) {
            // Remove the 'Permissions' navbar node in the Check permissions page.
            if ($this->page->pagetype === 'admin-roles-check') {
                $this->remove('permissions');
            }
        }

        if ($this->page->context->contextlevel == CONTEXT_COURSE) {
            // Remove the course breadcrumb node.
            $this->remove($this->page->course->id, \breadcrumb_navigation_node::TYPE_COURSE);

            // Add the categories breadcrumb navigation nodes.
            foreach (array_reverse($this->get_categories()) as $category) {
                $context = \context_coursecat::instance($category->id);
                if (!\core_course_category::can_view_category($category)) {
                    continue;
                }

                $displaycontext = \context_helper::get_navigation_filter_context($context);
                $url = new moodle_url('/course/index.php', ['categoryid' => $category->id]);
                $name = format_string($category->name, true, ['context' => $displaycontext]);
                $categorynode = \breadcrumb_navigation_node::create($name, $url, \breadcrumb_navigation_node::TYPE_CATEGORY,
                    null, $category->id);
                if (!$category->visible) {
                    $categorynode->hidden = true;
                }
                $this->items[] = $categorynode;
            }
        }

        // Remove any duplicate navbar nodes.
        $this->remove_duplicate_items();

        // Remove 'My courses' and 'Courses' if we are in the course context.
        $this->remove('mycourses');
        $this->remove('courses');

        // Remove the navbar nodes that already exist in the secondary navigation menu.
        $this->remove_items_that_exist_in_navigation($PAGE->secondarynav);

        switch ($this->page->pagetype) {
            case 'group-groupings':
            case 'group-grouping':
            case 'group-overview':
            case 'group-assign':
                // Remove the 'Groups' navbar node in the Groupings, Grouping, group Overview and Assign pages.
                $this->remove('groups');
            case 'backup-backup':
            case 'backup-restorefile':
            case 'backup-copy':
            case 'course-reset':
                // Remove the 'Import' navbar node in the Backup, Restore, Copy course and Reset pages.
                $this->remove('import');
            case 'course-user':
                $this->remove('mygrades');
                $this->remove('grades');
        }

        // Remove 'My courses' if we are in the module context.
        if ($this->page->context->contextlevel == CONTEXT_MODULE) {
            // TODO investigate why categories only shown in certain course contexts by default.
            // Add the categories breadcrumb navigation nodes.
            foreach ($this->get_categories() as $category) {
                $context = \context_coursecat::instance($category->id);
                if (!\core_course_category::can_view_category($category)) {
                    continue;
                }

                $displaycontext = \context_helper::get_navigation_filter_context($context);
                $url = new moodle_url('/course/index.php', ['categoryid' => $category->id]);
                $name = format_string($category->name, true, ['context' => $displaycontext]);
                $categorynode = \breadcrumb_navigation_node::create($name, $url, \breadcrumb_navigation_node::TYPE_CATEGORY,
                    null, $category->id);
                if (!$category->visible) {
                    $categorynode->hidden = true;
                }

                // Check if the category node already exists in the array.
                $exists = false;
                foreach ($this->items as $item) {
                    if ($item->type == \breadcrumb_navigation_node::TYPE_CATEGORY && $item->key == $category->id) {
                        $exists = true;
                        break;
                    }
                }

                // Add the category node only if it doesn't already exist.
                if (!$exists) {
                    array_unshift($this->items, $categorynode);
                }
            }

            $this->remove('mycourses');
            $this->remove('courses');
            // Remove the course category breadcrumb node.
            //$this->remove($this->page->course->category, \breadcrumb_navigation_node::TYPE_CATEGORY);
            $courseformat = course_get_format($this->page->course)->get_course();
            // Section items can be only removed if a course layout (coursedisplay) is not explicitly set in the
            // given course format or the set course layout is not 'One section per page'.
            $removesections = !isset($courseformat->coursedisplay) ||
                $courseformat->coursedisplay != COURSE_DISPLAY_MULTIPAGE;
        }

        if ($this->page->context->contextlevel == CONTEXT_SYSTEM) {
            // Remove the navbar nodes that already exist in the secondary navigation menu.
            $this->remove_items_that_exist_in_navigation($PAGE->secondarynav);
        }

        // Set the designated one path for courses.
        $mycoursesnode = $this->get_item('mycourses');
        if (!is_null($mycoursesnode)) {
            $url = new \moodle_url('/my/courses.php');
            $mycoursesnode->action = $url;
            $mycoursesnode->text = get_string('mycourses');
        }

        $this->remove_no_link_items($removesections);

        // Remove breadcrumb if only one element, otherwise this is bad UX (unless in course).
        if ($this->page->context->contextlevel != CONTEXT_COURSE) {
            if ($this->item_count() <= 1) {
                $this->clear_items();
            }
        }
    }

    /**
     * Get the course categories.
     *
     * @return boostnavbaritem[] Boost navbar items.
     */
    public function get_categories(): array {
        return $this->page->categories;
    }

    /**
     * Remove a boostnavbaritem from the boost navbar.
     *
     * @param  string|int $itemkey An identifier for the boostnavbaritem
     * @param  int|null $itemtype An additional type identifier for the boostnavbaritem (optional)
     */
    protected function remove($itemkey, ?int $itemtype = null): void {

        $itemfound = false;
        foreach ($this->items as $key => $item) {
            if ($item->key === $itemkey) {
                // If a type identifier is also specified, check whether the type of the breadcrumb item matches the
                // specified type. Skip if types to not match.
                if (!is_null($itemtype) && $item->type !== $itemtype) {
                    continue;
                }
                unset($this->items[$key]);
                $itemfound = true;
                break;
            }
        }
        if (!$itemfound) {
            return;
        }

        $itemcount = $this->item_count();
        if ($itemcount <= 0) {
            return;
        }

        $this->items = array_values($this->items);
        // Set the last item to last item if it is not.
        $lastitem = end($this->items);
        if (is_a($lastitem, 'breadcrumb_navigation_node') && !$lastitem->is_last()) {
            $lastitem->set_last(true);
        }
    }


    /**
     * Remove items that have no actions associated with them and optionally remove items that are sections.
     *
     * The only exception is the last item in the list which may not have a link but needs to be displayed.
     *
     * @param bool $removesections Whether section items should be also removed (only applies when they have an action)
     */
    protected function remove_no_link_items(bool $removesections = true): void {
        foreach ($this->items as $key => $value) {
            if (isset($lastitem) && is_a($lastitem, 'breadcrumb_navigation_node') && !$value->is_last() &&
                (!$value->has_action() || ($value->type == \navigation_node::TYPE_SECTION && $removesections))) {
                unset($this->items[$key]);
            }
        }
        $this->items = array_values($this->items);
    }
}
