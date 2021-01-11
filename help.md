# Tempest WeatherBot Help
The Tempest WeatherBot responds to a number of arguments and keywords explained below. When provided no argument (e.g. `/weather`)` the bot will respond with current conditions.

Example commands (e.g. `/weather [argument]`)

| Command | Description |
|---|---|
| `/weather <blank>` | Display current conditions |
| `/weather now` | Display current conditions |
| `/weather Tuesday` | Display the forecast for Tuesday |
| `/weather 85 hours` | Display the forecast 85 hours from now |
| `/weather 3 days` | Display the forecast three days from now |

---
## Forecast Range
The Tempest WeatherBot can respond to forecast inquiries _up to 10 days_ from the current time. This means arguments (`hour[s]`, `day[s]`, and `week`) should fall within the specified ranges. Arguments outside this range will return a private error or display the current conditions.

| Command | Argument Range |
|---|---|
| `/weather X hours` | X can range `1` to `120` |
| `/weather X days` | X can range `1` to `10` |
| `/weather X week` | X can only be `1` |

---
## Bot Keyword Actions
The Tempest WeatherBot has unique keywords unrelated to the weather conditions or forecast. Supported keyword actions:

| Command | Description |
|---|---|
| `/weather help` | Display this help information |
| `/weather 6 hours private` | Display the forecast 6 hours from now with a private response |

The `private` keyword can be appended to the end of any command to privately respond to the calling user. This keyword _**must**_ be the last argument in all commands.

---
## Exampe Responses
  `/weather <blank>` or `/weather now`:

  ![Example current conditions response](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/current.png?raw=true)


  `/weather Thursday`:

  ![Example "Thursday" forecast](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/thursday.png?raw=true)


  `/weather 110 hours`:

  ![Example +110-hour forecast](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/110hour.png?raw=true)


  `/weather 2 days`:

  ![Example +2 day forecast](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/2day.png?raw=true)


  `/weather tomorrow private`:
  
  ![Example "tomorrow" forecast with a private response](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/tomorrow-private.png?raw=true)