# Slack Tempest WeatherBot
A Slack bot to interact with a Tempest weather station, bringing local weather data into your favorite Slack channels. Developed by [@zaskem](https://github.com/zaskem) to do something useful and practical with data from his Tempest weather station via its remote API.

## Requirements
To run the bot code, the following things are required:

* A [WeatherFlow Tempest station](https://weatherflow.com/tempest-weather-system/) and an active [Tempest API token](https://tempestwx.com/settings/tokens);
* A Slack Workspace you control or in which you can request an App be installed;
* A Slack App with a Bot account and token, properly configured slash command (e.g. `/weather`), and OAuth scopes for `chat:write` and `users:read` enabled; and
* A host on which to run this code: a browsable path/URL for the slash command request, and a non-browsable path for the rest of the bot code.

### Tempest station and API
In setting up a Tempest weather station most of the work is automatically handled to obtain API access. Once the station starts checking in, creating a [Tempest API token](https://tempestwx.com/settings/tokens) is a self-service action. As the station ID and other information is obtained from automatically-generated metadata, a token is the only piece required to get started using the API. WeatherFlow provides a reasonable amount of evolving information about the [Tempest API & Developer Platform](https://weatherflow.github.io/Tempest/).

### Slack App/Bot and the Slack API
Creating a [Slack App](https://api.slack.com/apps/) and associated Bot is outside the scope of this README. You will need to create a new App, configure a slash command of your choice (e.g. `/weather`), and add OAuth scopes for `chat:write` and `users:read`. Install the App to a testing channel, and copy much of the information in the `App Credentials` section of your App's `Basic Information` page.

#### A note about creating a listening event:
This bot can be modified to respond to an Event in addition to or instead of using a slash command. An event subscription for `app_home_opened` is required for the optional app home functionality, for example. In configuring an event subscription, you will need to respond to a one-time challenge request. An example responder (`challengeresponder.php`) is included in the `eventlistener/` directory for reference.

## Bot Configuration and First Run Instrcutions
Three configuration files exist in the `config/` directory. Example/Stubout versions are provided, and each of these files should be copied without the `.example` extension (e.g. `cp bot.php.example bot.php`):

* `bot.php`
* `slack.php`
* `tempest.php`

Edit each file as necessary for your bot. Note that as the bot goes into production, several dynamic `[title].generated.php` files will also end up in the `config/` directory. They, and files automatically generated in the `config/history/` directory, can be ignored and should not be edited.

Before the bot can properly respond to requests, two scripts must be manually invoked on the bot host to generate access lists and metadata:
1. `php SlackUsers.php` will generate the "access list" of accounts in your Workspace, which is required to loosely "authenticate" a valid request.
2. `php GenerateStationMetadata.php` will obtain the Tempest station metadata, which is required for all other Tempest API calls.

Assuming both commands complete without issue, you can "install" the web request handler for your slash command. It is very important to understand that the `requesthandler/index.php` file is _not_ intended to be present at the same location as the rest of the bot source. `requesthandler/index.php` should be copied to the path you specified as the `Request URL` of your slash command and edit line 6 (`$botCodePath`) accordingly. This ensures separation between components of the bot (e.g. keeping bot source and keys not publicly-available).

### Optional "App Home" Functionality
If desired, the "App Home Tab" can be easily enabled for this bot. Enabling the bot app home tab provides a condensed single-page view of current conditions, a four-hour forecast, and the five-day forecast without invoking any commands, and is refreshed each time the tab is opened.

A handful of additional items must be configured to enable the home tab:
* Enable `Home Tab` on the bot's `App Home` feature settings;
* Install an Event Request URL (listener) and create an Event Subscription for the `app_home_opened` bot event; and
* Copy the event listener request handler to the path specified as the `Request URL` of your Event Subscriptions and edit line 6 (`$botCodePath`) accordingly.

As with the standard slash command request handler, it is very important to understand that the `eventlistener/index.php` file is _not_ intended to be present at the same location as the rest of the bot source.

### Ongoing Configuration Update Cadence
In theory, the station metadata would rarely, if ever, change. Additionally, depending on your Slack Workspace, your user base may rarely change. It is of good form to periodically re-run the `SlackUsers.php` and `GenerateStationMetadata.php` scripts as a refresh. For higher-request environments, the history data files could also take up more local disk space than desired. A `CleanupHistory.php` script is included to easily purge the history cache.

While these scripts can be manually invoked on some interval, they can also be easily invoked with cron (example: quarterly at 03:30 for users and metadata, and semi-annually at 06:00 for history files):
```bash
30 3 1 */3 * /path/to/php /path/to/SlackUsers.php
30 3 1 */3 * /path/to/php /path/to/GenerateStationMetadata.php
0 6 1 */6 * /path/to/php /path/to/CleanupHistory.php
```

## Bot Usage
On installation and configuration, interact with the bot accordingly in Slack: `/weather tomorrow`

If all is successful, some fancy output like this will be presented:
![Example image of "tomorrow" forecast](https://github.com/zaskem/slackbot-tempestweather/blob/main/images/tomorrow.png?raw=true)

Almost every response from the bot will include a note about the `help` argument. Using this command provides substantially more information about the bot's options. Most [help content is available on the project site](https://tempestweatherbot.mzonline.com/help.html).

## Slack Posting
By design the bot defaults to posting its response in the channel in which the command was entered. However, the user has an option to quell the post by including the `private` keyword as the last argument in a command (e.g. `/weather Friday private`). "Public" responses are submitted via the Web API `chat.postMessage` endpoint. Private responses are submitted via the temporary `response_url` webhook provided in the `POST` arguments to the request handler. Debug, help, and error information is generally relayed as part of the request's Acknowledgement response.

## Troubleshooting
There are several possible causes of trouble in using the bot. Generally speaking the key starting points in troubleshooting would include:

* Failure to source data from Tempest;
* A super wonky time argument that falls outside the bot's expected values or argument structure (now ... +10 days);
* Misconfigured request or event responder URLs (often the result of not properly setting `$botCodePath`); and
* Bad block structure/JSON for the Slack response.

Setting `$debug_bot` to `true` in `config/bot.php` will output much information about the process and arguments provided. These are relayed in the Acknowledgement response for a given command and will show up as a private response in Slack.

## Enhancements on the Radar
Several [enhancements](https://github.com/zaskem/slackbot-tempestweather/labels/enhancement) are in the queue for future development. These are tracked and managed via GitHub.