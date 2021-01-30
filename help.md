# Tempest WeatherBot Help
The Tempest WeatherBot responds to a number of arguments. When provided no argument (e.g. `/weather`)` the bot will respond with current conditions.

Example commands (e.g. `/weather [argument]`)

| Command | Description |
|---|---|
| `/weather <blank>` | Display current conditions |
| `/weather now` | Display current conditions |
| `/weather private` | Display current conditions privately |
| `/weather Tuesday` | Display the forecast for Tuesday |
| `/weather 8 hour` | Display the forecast +8 hours from now |
| `/weather 85 hours` | Display the forecast 85 hours from now |
| `/weather 3 days` | Display the forecast three days from now |
| `/weather next 5 days` | Display the five-day forecast |
| `/weather today` | Display summary for today |
| `/weather yesterday` | Display summary for yesterday |
| `/weather last month` | Display summary for last month |

---
## Bot Keywords
The Tempest WeatherBot also responds to unique keywords:

The `private` keyword can be appended to the _end_ of any command to privately respond to the calling user. This keyword _**must**_ be the last argument in all commands.

Detailed `help` topics are also supported.

| Command | Description |
|---|---|
| `/weather 6 hours private` | Display the forecast 6 hours from now with a private response |
| `/weather help` | Display general help information |
| `/weather help conditions` | Help regarding current conditions |
| `/weather help forecast` | Help regarding station forecasts |
| `/weather help history` | Help regarding history summaries |

---
## Current Conditions
The Tempest WeatherBot's default behavior is to display the current/most recent conditions:

| Command | Description |
|---|---|
| `/weather <blank>` | Display current conditions |
| `/weather now` | Display current conditions |
| `/weather private` | Display current conditions privately |

---
## Forecast Commands and Range
The Tempest WeatherBot can respond to forecast inquiries _up to 10 days_ from the current time. **Arguments** (`hours`, `days`, and `week`) should fall within the specified ranges:

| Command | Argument Range |
|---|---|
| `/weather X hour[s]` | X can range `0` to `240`. Display the forecast for the specified hour |
| `/weather X day[s]` | X can range `0` to `10`. Display the hour-specific forecast +X day[s] |
| `/weather X week` | X can only be `1` |

When providing a numeric request (e.g. `X hours`) the _hour-specific_ forecast will be returned based on the request time. However, an `X days` request made at 5 p.m. will return the _daily_ forecast for `X` days from now.

**Relative keywords** (`tomorrow`, `next`, and weekday names) can also be used:

| Command | Description |
|---|---|
| `/weather tomorrow` | Display forecast for tomorrow |
| `/weather Tuesday` | Display forecast for Tuesday |
| `/weather next 24 hours` | Display forecasts for the next 24 hours |
| `/weather next 10 days` | Display forecasts for the next 10 days |

When providing a numeric relative request (e.g. `next X [hours/days/week]`) `X` must fall in the ranges identified above. Relative `day` and `week` requests will return daily forecasts for the period. Relative `hour` forecasts will generate dynamically-appropriate intervals for the period, generally not to exceed 10 individual forecasts per request.

---
## History Commands and Range
The Tempest WeatherBot can respond with daily station history summaries _on or **after**_ the `$bot_historyStarts` date.

Specify a specific date or date range. `dateString` should be in `YYYY-MM-DD` or `DD-MM-YYYY` format:

| Command | Argument Range |
|---|---|
| `/weather X hour/day/week/month[s]` | X is a **negative** number within range. Display summary for the matching date |
| `/weather dateString` | Display summary for the matching date |
| `/weather dateString to dateString` | Display summary for the submitted period |

**Relative keywords** (`today`, `yesterday`, `last`, and `this`) can also be used:
| Command | Description |
|---|---|
| `/weather today` | Display daily summary for yesterday |
| `/weather yesterday` | Display daily summary for yesterday |
| `/weather last week/month/year` | Display summary for the requested period |
| `/weather this week/month/year` | Display summary for the requested period (through the current day). |

Note: `week`s are relative to Mondays.

---

## Example Responses in Slack

### Current Conditions

  `/weather <blank>` or `/weather now`:

  ![Example current conditions response](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/current.png?raw=true)

---

### Weather Forecasts

  `/weather Thursday`:

  ![Example "Thursday" forecast](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/thursday.png?raw=true)


  `/weather 110 hours`:

  ![Example +110-hour forecast](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/110hour.png?raw=true)


  `/weather 2 days`:

  ![Example +2 day forecast](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/2day.png?raw=true)


  `/weather next 3 days`:

  ![Example three-day forecast](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/next3days.png?raw=true)


  `/weather tomorrow private`:
  
  ![Example "tomorrow" forecast with a private response](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/tomorrow-private.png?raw=true)

---

### Weather Summaries (history)

  `/weather yesterday`:

  ![Example daily summary](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/yesterday.png?raw=true)


  `/weather last month`:

  ![Example month summary](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/lastmonth.png?raw=true)


  `/weather 2020-11-15`:

  ![Example day summary for November 15, 2020](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/2020-11-15.png?raw=true)


  `/weather 2020-10-10 to 2020-10-11`:

  ![Example two consecutive day summary for October 10-11, 2020](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/2020-10-10-2020-10-11.png?raw=true)
  
---

### Bot Home Tab
The bot has an available home tab which, if optionally enabled, provides for a condensed single-page view of current conditions, four-hour forecast, and five-day forecast.

  ![Example bot home tab view](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/hometab.png?raw=true)
