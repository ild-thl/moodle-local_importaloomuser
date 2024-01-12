# moodle-local_importaloomuser
# Moodle Local Import Aloom User Plugin

This plugin allows you to import users from an external service via API into Moodle and enrol them into
a specific course and in this course in a specific group.

## Installation

1. Download the latest version of the plugin from Github.
2. Extract the plugin files to the `local/importaloomuser` directory in your Moodle installation.
3. Log in to your Moodle site as an administrator.

## Usage

1. After installing the plugin, go to the **Site administration** > **Plugins** > **Local plugins** > **Import Aloom User**.
2. Configure the plugin settings according to your requirements.
3. There is a cron jobs that will take the following actions: 
    - getting data from the external service using the API
    - preparing the data to use as data to import users into Moodle and enrol them into courses and groups
4. To start the full import process manually in the browser interface, call `yoursite/local/importaloomuser/index.php` and follow the steps.

## Requirements

- Moodle 3.0 or later
- Aloom user data API providing data in json-format

## License

This plugin is licensed under the [GNU General Public License](https://www.gnu.org/licenses/gpl-3.0.en.html). 
