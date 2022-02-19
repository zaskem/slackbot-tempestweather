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
This bot can be modified to respond to an Event in addition to or instead of using a slash command. An event subscription for `app_home_opened` is required for the optional app home functionality, for example. In configuring an event subscription, you will need to respond to a one-time challenge request. An example responder (`challengeresponder.php`) is included in the `web/eventlistener/` directory for reference.

## Bot Configuration and First Run Instrcutions
Three configuration files exist in the `config/` directory. Example/Stubout versions are provided, and each of these files should be copied without the `.example` extension (e.g. `cp bot.php.example bot.php`):

* `bot.php`
* `nws.php`
* `slack.php`
* `tempest.php`

Edit each file as necessary for your bot. Note that as the bot goes into production, several dynamic `[title].generated.php` files will also end up in the `config/` directory. They, and files automatically generated in the `config/history/` directory, can be ignored and should not be edited.

Before the bot can properly respond to requests, two scripts must be manually invoked on the bot host to generate access lists and metadata:
1. `php jobs/SlackUsers.php` will generate the "access list" of accounts in your Workspace, which is required to loosely "authenticate" a valid request.
2. `php jobs/GenerateTempestMetadata.php` will obtain the Tempest station metadata, which is required for all other Tempest API calls.

Assuming both commands complete without issue, you can "install" the request handler for your slash command and/or other features.

## Web-Accessible Handler Installation Instructions
The repo provides a default `web/` directory, intended to be configured as the document root of the bot host (e.g. the `DocumentRoot /path/to/repo/web` Apache configuration directive). Assuming your web host configuration is set to use the `web/` directory as the Internet-facing root of your bot (e.g. `https://weatherbot.example.com`), no further server-side configuration is necessary to invoke the bot. The slash command can then be set up with a `Request URL` such as `https://weatherbot.example.com/requesthandler/`.

This default configuration also applies to optional "App Home" functionality handlers if enabled.

### Optional "App Home" Functionality
If desired, the "App Home Tab" can be easily enabled for this bot. Enabling the bot app home tab provides a condensed single-page view of current conditions and alerts, a four-hour forecast, and the five-day forecast without invoking any commands, and is refreshed each time the tab is opened.

A handful of additional items must be configured to enable the home tab and its interactive components:
* Enable `Home Tab` on the bot's `App Home` feature settings;
* Install an Event Request URL (listener) and create an Event Subscription for the `app_home_opened` bot event; and
* Copy the event listener request handler to the path specified as the `Request URL` of your Event Subscriptions and edit line 6 (`$botCodePath`) accordingly.
* Enable `Interactivity` on the bot's `Interactivity & Shortcuts` feature settings;
* Install an Interactive Request URL (listener) in the Interactivity section of Interactivity & Shortcuts options for the bot; and
* Copy the interactive listener request handler to the path specified as the `Request URL` of your Interactivity & Shortcuts and edit line 6 (`$botCodePath`) accordingly.

As with the standard slash command request handler, it is very important to understand that the `eventlistener/index.php` and `interactivelistener/index.php` files are _not_ intended to be present at the same location as the rest of the bot source.

#### Initial Release (v1.0.0) Compatibility or Handler Customization Notes
The initial release (v1.0.0) had a mixed repository root directory structure, where Internet-facing directories were mixed with non-Internet-facing directories and files. This necessitated copying these directories and files to a different location/document root, as the `requesthandler/index.php`, `eventlistener/index.php`, and `interactivelistener/index.php` files are the _only_ files intended to be Internet- or web-accessible. The bot source files should _not_ be Internet-facing to ensure separation between components of the bot (e.g. keeping bot source and keys not publicly-available).

This configuration required additional effort when updating to ensure "new" versions were properly moved to the document root. If you were using v1.0.0, changing your web server configuration to use the `web/` path as the document root is recommended for simplicity. However, with manual intervention it is still possible to use an alternative document root for the handlers if desired or necessary (e.g. you do not have direct control over the bot's document root). Moving from the v1.0.0 release (or its vintage) without understanding this structure change may cause your bot to break in unpredictable ways.

## Bot Maintenace and Performance Options

### Ongoing Configuration Update Cadence
In theory, the station metadata rarely, if ever, changes. Additionally, depending on your Slack Workspace, your user base may rarely change. It is of good form to periodically re-run the maintenance scripts in the `jobs/` directory such as `SlackUsers.php` and `GenerateStationMetadata.php`. For higher-request environments, the history data files could also take up more local disk space than desired. A `CleanupHistory.php` script is included to easily purge the history cache.

While these scripts can be manually invoked on some interval, they can also be easily invoked with cron (example: quarterly at 03:30 for users and metadata, and semi-annually at 06:00 for history files):
```bash
30 3 1 */3 * /path/to/php /path/to/jobs/SlackUsers.php
30 3 1 */3 * /path/to/php /path/to/jobs/GenerateStationMetadata.php
0 6 1 */6 * /path/to/php /path/to/jobs/CleanupHistory.php
```

### NWS API Performance Improvement
If either/both `$useNWSAPIAlerts` or `$useNWSAPIForecasts` variables are set to `true` (see `config/nws.php` lines 6-7), features of the [NWS API](https://www.weather.gov/documentation/services-web-api) are enabled for the bot.

The NWS API provides a lot of additional value and usefulness to the bot; however, using the NWS API requires additional external API requests. In most circumstances the bot will behave without issue, though there's an additional latency involved which can (and does) periodically cause the bot to not respond in a timely/expected manner. These timeouts manifest in different non-fatal ways (delayed updates or timeout messages).

To improve bot performance, two scripts are included in the `jobs/` directory:
* `RefreshNWSAlertData.php` - Cache NWS Alert Data
* `RefreshNWSForecastData.php` - Cache NWS Forecast Data (Hourly and Regular 7-Day)

The scripts are designed to be invoked with cron, for example (alerts every 10 minutes and forecasts at 20 past each hour):
```bash
*/10 * * * * /path/to/php /path/to/jobs/RefreshNWSAlertData.php
20 * * * * /path/to/php /path/to/jobs/RefreshNWSForecastData.php
```

`RefreshNWSAlertData.php` will _always_ update the alert data file, overriding the default value set for maximum age of the alert data file (10 minutes). In testing and practice, 10 minutes has seemed an appropriate update cadence; your mileage may vary. Set this interval to your desired window.

`RefreshNWSForecastData.php` will update the hourly and regular 7-day forecast data files, respecting the values (`$nwsAPIHourlyForcastCadence` and `$nwsAPIForecastCadence` set on lines 8-9 in `config/nws.php`). The default values should be sufficient, including the cron example, as the NWS typically updates hourly forecasts around 10 or 15 minutes past each hour, and updates the regular forecast around every 6 hours (typically around 04:00, 10:00, 16:00, and 22:00). Set the cron and cadence intervals to your desired windows; however, know that the data does not typically change with greater frequency than hourly, and the NWS API service may consider more frequent requests as an abuse of the service.

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

Setting `$debug_bot` to `true` in `config/bot.php` will output much information about the process and arguments provided. These are relayed in the Acknowledgement response for a given command and will show up as a private response in Slack. Further troubleshooting can be handled by monitoring the PHP/Apache `error_log` file.

#### A note about debugging the App Home Tab:
In most circumstances, strange behavior on the App Home tab is caused by processing delays (stalled API responses) or something unexpected in the block structure. Setting `$debug_bot` to `true` in `config/bot.php` will _rarely_ help troubleshoot such block-building issues due to how the event listener behaves.

## Enhancements on the Radar
Several [enhancements](https://github.com/zaskem/slackbot-tempestweather/labels/enhancement) are in the queue for future development. These are tracked and managed via GitHub.