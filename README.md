# slackbot-tempestweather
A Slack bot to interact with a Tempest weather station, bringing local weather data into your favorite Slack channels. Developed by @zaskem to do something useful and practical with data from his Tempest weather station via its remote API.

## Requirements
To run the bot code, the following things are required:

* A [WeatherFlow Tempest station](https://weatherflow.com/tempest-weather-system/) and an active [Tempest API token](https://tempestwx.com/settings/tokens);
* A Slack Workspace you control or in which you can request an App be installed;
* A Slack App with a Bot account and token, properly configured slash command (e.g. `/weather`), and OAuth scopes for `chat:write` and `users:read` enabled; and
* A host on which to run this code: a browsable path/URL for the slash command request, and a non-browsable path for the rest of the bot code.

### Tempest station and API
In setting up a Tempest weather station, most of the work is automatically handled to obtain API access. Once the station starts checking in, creating a [Tempest API token](https://tempestwx.com/settings/tokens) is a self-service action. As the station ID and other information is obtained from the automatically-generated metadata, a token is the only information required to get started using the API. WeatherFlow provides a reasonable amount of evolving information about the [Tempest API & Developer Platform](https://weatherflow.github.io/Tempest/).

### Slack App/Bot and the Slack API
Creating a [Slack App](https://api.slack.com/apps/) and associated Bot is outside the scope of this README. You will need to create a new App, configure a slash command of your choice (e.g. `/weather`), and add OAuth scopes for `chat:write` and `users:read`. Install the App to a testing channel, and copy much of the information in the `App Credentials` section of your App's `Basic Information` page.

#### A note about creating a listening event:
This bot can be modified to respond to en Event in addition to or instead of using a slash command. In configuring an Event listener, you will need to respond to a challenge request. An example responder (`challengeresponder.php`) is included in the `requesthandler/` directory for reference.

## Bot Configuration and First Run Instrcutions
Three configuration files exist in the `config/` directory. Example/Stubout versions are provided, and each one of these files should be copied without the `.example` extension (e.g. `cp bot.php.example bot.php`):

* `bot.php`
* `slack.php`
* `tempest.php`

Edit each file as necessary for your bot. Note that as the bot goes into production, several dynamic `[title].generated.php` files will also end up in this directory. They can be ignored and should not ever need to be edited.

Before the bot can properly respond to requests, two scripts must be manually invoked on the bot host to generate access lists and metadata:
1. `php SlackUsers.php` will generate the "access list" of accounts in your Workspace, which is required to loosely "authenticate" a valid request.
2. `php GenerateStationMetadata.php` will obtain the Tempest station metadata, which is required for all other Tempest API calls.

Assuming both commands complete without issue, you can "install" the web request handler for your slash command. It is very important to understand that the `requesthandler/index.php` file is _not_ intended to be present at the same location as the rest of the bot source. This file should be copied to the path you specified as the `Request URL` of your slash command. Copy this file to the appropriate path and edit line 6 (`$botCodePath`) accordingly.

### Ongoing Configuration Update Cadence
In theory, the station metadata would rarely, if ever, change. Additionally, depending on your Slack Workspace your user base may rarely change. It is of good form to periodically re-run the `SlackUsers.php` and `GenerateStationMetadata.php` scripts as a refresh. This could be done manually on some interval, or easily done with cron (example: quarterly at 03:30):
```bash
30 3 1 */3 * /path/to/php /path/to/SlackUsers.php
30 3 1 */3 * /path/to/php /path/to/GenerateStationMetadata.php
```

## Bot Usage
On installation and configuration, interact with the bot accordingly in Slack: `/weather tomorrow`

If all is successful, some fancy output like this will be presented:
![Example image of "tomorrow" forecast](https://github.com/zaskem/slackbot-tempestweather/blob/main/images/tomorrow.png?raw=true)

Almost every response from the bot will include a note about the `help` argument. Using this command provides substantially more information about the bot's options.

## Slack Posting
By design the bot defaults to posting its response in the channel in which the command was entered. However, the user has an option to quell this by including the `private` keyword as the last argument in a command (e.g. `/weather Friday private`). Non-private responses are submitted via the Web API `chat.postMessage` endpoint. Private responses are submitted via the temporary `response_url` webhook provided in the `POST` arguments to the request handler. Debug, help, and error information is generally relayed as part of the request's Acknowledgement response.

## Troubleshooting
There are several possible causes of trouble in using the bot. Generally speaking the key starting points in troubleshooting would include:

* Failure to source data from Tempest;
* A super wonky time argument that falls outside the bot's expected values or argument structure (now ... +10 days); and
* Bad block structure/JSON for the Slack response.

Setting `$debug_bot` to `true` in `config/bot.php` will output much information about the process and arguments provided. These are relayed in the Acknowledgement response for a given command and will show up as a private response in Slack.

## Enhancements on the Radar
Several [enhancements](https://github.com/zaskem/slackbot-tempestweather/labels/enhancement) are in the queue for future development. These are tracked via GitHub, but key enhancements on the radar include:

* Handling lightning data with announcements
* Obtaining and doing things with previous observations