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
 * Citricity xund testing utilities.
 *
 * @package    theme_citricitxund
 * @author     Guy Thomas
 * @copyright  2022 Citricity Ltd <http://citr.city> / FFHS MediaFactory
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_citricityxund;

defined('MOODLE_INTERNAL') || die();

class util {
    public static function get_restricted_property_value($object, string $property) {
        $reflect = new \ReflectionClass($object);
        $prop = $reflect->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }
}