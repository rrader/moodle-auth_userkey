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
 * Tests for auth_plugin_userkey class.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for auth_plugin_userkey class.
 *
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_userkey_testcase extends advanced_testcase {
    /**
     * An instance of auth_plugin_userkey class.
     * @var auth_plugin_userkey
     */
    protected $auth;

    /**
     * Initial set up.
     */
    public function setUp() {
        global $CFG;
        require_once($CFG->dirroot . '/auth/userkey/tests/fake_userkey_manager.php');
        require_once($CFG->dirroot . '/auth/userkey/auth.php');

        $this->auth = new auth_plugin_userkey();
        $this->resetAfterTest();
    }

    /**
     * Test that users can't login using login form.
     */
    public function test_users_can_not_login_using_login_form() {
        $user = new stdClass();
        $user->auth = 'userkey';
        $user->username = 'username';
        $user->password = 'correctpassword';

        self::getDataGenerator()->create_user($user);

        $this->assertFalse($this->auth->user_login('username', 'correctpassword'));
        $this->assertFalse($this->auth->user_login('username', 'incorrectpassword'));
    }

    /**
     * Test that the plugin doesn't allow to store users passwords.
     */
    public function test_auth_plugin_does_not_allow_to_store_passwords() {
        $this->assertTrue($this->auth->prevent_local_passwords());
    }

    /**
     * Test that the plugin is external.
     */
    public function test_auth_plugin_is_external() {
        $this->assertFalse($this->auth->is_internal());
    }

    /**
     * Test that the plugin doesn't allow users to change the passwords.
     */
    public function test_auth_plugin_does_not_allow_to_change_passwords() {
        $this->assertFalse($this->auth->can_change_password());
    }

    public function test_get_default_mapping_field() {
        $expected = 'email';
        $actual = $this->auth->get_mapping_field();

        $this->assertEquals($expected, $actual);
    }

    public function test_get_mapping_field() {
        set_config('mappingfield', 'username', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $expected = 'username';
        $actual = $this->auth->get_mapping_field();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \invalid_parameter_exception
     */
    public function test_throwing_exception_if_mapping_field_is_not_provided() {
        $user = array();
        $actual = $this->auth->get_login_url($user);
    }

    /**
     *
     */
    public function test_text_of_throwing_exception_if_mapping_field_is_not_provided() {
        $user = array();

        try {
            $actual = $this->auth->get_login_url($user);
        } catch (\invalid_parameter_exception $e) {
            $actual = $e->getMessage();
            $expected = 'Invalid parameter value detected (Required field "email" is not set or empty.)';

            $this->assertEquals($expected, $actual);
        }

        set_config('mappingfield', 'username', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();
        try {
            $actual = $this->auth->get_login_url($user);
        } catch (\invalid_parameter_exception $e) {
            $actual = $e->getMessage();
            $expected = 'Invalid parameter value detected (Required field "username" is not set or empty.)';

            $this->assertEquals($expected, $actual);
        }
    }

    /**
     *
     * @expectedException \invalid_parameter_exception
     */
    public function test_throwing_exception_if_user_is_not_exist() {
        $user = array();
        $user['email'] = 'notexists@test.com';

        $actual = $this->auth->get_login_url($user);
    }

    /**
     *
     */
    public function test_return_correct_login_url_if_user_is_array() {
        global $CFG;

        $user = array();
        $user['username'] = 'username';
        $user['email'] = 'exists@test.com';

        self::getDataGenerator()->create_user($user);

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $expected = $CFG->wwwroot . '/auth/userkey/login.php?key=FaKeKeyFoRtEsTiNg';
        $actual = $this->auth->get_login_url($user);

        $this->assertEquals($expected, $actual);
    }

    /**
     *
     */
    public function test_return_correct_login_url_if_user_is_object() {
        global $CFG;

        $user = new stdClass();
        $user->username = 'username';
        $user->email = 'exists@test.com';

        self::getDataGenerator()->create_user($user);

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $expected = $CFG->wwwroot . '/auth/userkey/login.php?key=FaKeKeyFoRtEsTiNg';
        $actual = $this->auth->get_login_url($user);

        $this->assertEquals($expected, $actual);
    }


    public function test_return_correct_login_url_if_user_is_object_using_default_keymanager() {
        global $DB, $CFG;

        $user = array();
        $user['username'] = 'username';
        $user['email'] = 'exists@test.com';

        $user = self::getDataGenerator()->create_user($user);

        create_user_key('auth/userkey', $user->id);
        create_user_key('auth/userkey', $user->id);
        create_user_key('auth/userkey', $user->id);
        $keys = $DB->get_records('user_private_key', array('userid' => $user->id));

        $this->assertEquals(3, count($keys));

        $actual = $this->auth->get_login_url($user);

        $keys = $DB->get_records('user_private_key', array('userid' => $user->id));
        $this->assertEquals(1, count($keys));

        $actualkey = $DB->get_record('user_private_key', array('userid' => $user->id));

        $expected = $CFG->wwwroot . '/auth/userkey/login.php?key=' . $actualkey->value;

        $this->assertEquals($expected, $actual);
    }

}
