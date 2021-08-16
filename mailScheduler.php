<?php
/**
 * mailScheduler : Set up and configure survey reminders per participant
 *
 * @author Timothy Waldron <tim@waldrons.com.au>
 * @copyright 2021 Timothy Waldron <https://www.waldrons.com.au>
 * @license AGPL v3
 * @version 4.0.4-beta1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

class mailScheduler extends PluginBase
{
  protected $storage = 'DbStorage';
  static protected $name = 'mailScheduler';
  static protected $description = 'Set up and configure survey reminders per participant';
}