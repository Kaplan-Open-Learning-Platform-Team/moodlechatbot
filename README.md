# Moodle Chat Bot Plugin

## Overview
The Moodle Chat Bot plugin is an activity module that integrates an AI-powered chatbot into Moodle courses. It uses the Groq API to provide intelligent responses to student queries, enhancing the learning experience.

## Features
- AI-powered chat interface within Moodle courses
- Configurable AI model selection
- Adjustable response length
- Easy-to-use interface for students and teachers

## Requirements
- Moodle 3.9 or higher
- PHP 7.3 or higher
- Groq API key

## Installation
1. Download the plugin and extract it to the `mod/moodlechatbot` directory in your Moodle installation.
2. Log in as an administrator and go to Site Administration > Notifications to complete the installation.
3. Go to Site Administration > Plugins > Activity Modules > Moodle Chat Bot to configure the global settings.
4. Enter your Groq API key and select your preferred AI model.

## Usage
1. In a course, turn editing on and add a "Moodle Chat Bot" activity.
2. Configure the activity settings as desired.
3. Students can then interact with the chat bot by entering messages in the chat interface.

## Configuration
Global settings (Site Administration > Plugins > Activity Modules > Moodle Chat Bot):
- Groq API Key: Enter your Groq API key here.
- AI Model: Select the AI model to use for responses.
- Max Tokens: Set the maximum number of tokens for AI responses.

## Contributing
Contributions to the Moodle Chat Bot plugin are welcome. Please submit pull requests to the GitHub repository.

## License
This plugin is licensed under the GNU GPL v3 or later. See the LICENSE file for details.

## Support
For support, please open an issue on the GitHub repository or contact the plugin maintainer.

## Authors
[Your Name] <your.email@example.com>

## Acknowledgements
This plugin uses the Groq API for generating AI responses.
