The Slack Tempest Weather Bot was developed by [@zaskem](https://github.com/zaskem) to do something useful and practical with data from his Tempest weather station via its remote API. 

The bot itself requires some form of personal access to a [WeatherFlow Tempest station](https://weatherflow.com/tempest-weather-system/). Per the Tempest [Remote Data Access Policy](https://weatherflow.github.io/Tempest/api/remote-developer-policy.html) this is to be your own station, or definitely a non-commercial endeavor with a close friend otherwise.

The general idea is to have a Slack slash command (e.g. `/weather`) which responds with current conditions, a forecast, or other information in real-time. Tempest has web and mobile apps, but for folks who are always/often in Slack it's simply more convenient and cool. An example response for current conditions:

![example response for current conditions](https://github.com/zaskem/slackbot-tempestweather/blob/gh-pages/images/current.png?raw=true)

## Bot Data Source and Information Flow
Data is sourced from the Tempest API, and a response crafted based on the Slack command arguments. Users can optionally add the `private` keyword in their command (e.g. `/weather tomorrow private`) which will send a private response versus the default behavior of posting to the current channel. Posts to Slack happen with a combination of Slack's webhooks (private and error/debugging responses) and the Slack Web API.

## Bot Source Code
The [GitHub repo](https://github.com/zaskem/slackbot-tempestweather) contains the basics for getting started with the bot in your own Slack workspace.

## Bot Help & Usage Information
The "online" [version of the bot's help/usage content](help.md) includes example response images for folks not familiar with Slack.