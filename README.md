<p align="center">
  <img src="public/assets/images/logo.svg" width="250" alt="Logo" >
</p>

<h1 align="center">Statistics for Strava</h1>

<p align="center">
<a href="https://github.com/artop123/strava-statistics/actions/workflows/ci.yml"><img src="https://github.com/artop123/strava-statistics/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
<a href="https://github.com/artop123/strava-statistics/actions/workflows/docker-image.yml"><img src="https://github.com/artop123/strava-statistics/actions/workflows/docker-image.yml/badge.svg" alt="Publish Docker image"></a>
<a href="https://hub.docker.com/r/artop/strava-statistics"><img src="https://img.shields.io/docker/image-size/artop/strava-statistics" alt="Docker Image Size"></a>
<a href="https://hub.docker.com/r/artop/strava-statistics"><img src="https://img.shields.io/docker/pulls/artop/strava-statistics" alt="Docker pulls"></a>
</p>

---
<h4 align="center">Strava Statistics is a self-hosted web app designed to provide you with better stats.</h4>

<p align="center">
  <a href="https://github.com/robiningelbrecht/strava-statistics">View the original project for installation instructions and more details</a>
</p>

## What is different

* FTP is now calculated automatically from your activities. Each activity now displays its calculated eFTP
* FTP chart has been replaced with estimated FTP, showing different FTP values for running and cycling
* Activity intensity is determined based on estimated FTP
* Activities are sortable by FTP and best power outputs
* Weight can be imported [from a json file](#weightjson)
* Weight history chart is displayed if there is atleast five measurements
* And some other minor fixes 👀

### docker-compose.yml

```yml
services:
  app:
    image: artop/strava-statistics:latest
    volumes:
      - ./build:/var/www/build
      - ./storage/database:/var/www/storage/database
      - ./storage/files:/var/www/storage/files
      #- ./storage/weight.json:/data/weight.json
    env_file: ./.env
    ports:
      - 8080:8080
```

### .env

```bash
# The URL on which the app will be hosted. This URL will be used in the manifest file. 
# This will allow you to install the web app as a native app on your device.
MANIFEST_APP_URL=http://localhost:8080/
# The client id of your Strava app.
STRAVA_CLIENT_ID=YOUR_CLIENT_ID
# The client secret of your Strava app.
STRAVA_CLIENT_SECRET=YOUR_CLIENT_SECRET
# The refresh of your Strava app.
STRAVA_REFRESH_TOKEN=YOUR_REFRESH_TOKEN
# Strava API has rate limits (https://github.com/robiningelbrecht/strava-statistics/wiki),
# to make sure we don't hit the rate limit, we want to cap the number of new activities processed
# per import. Considering there's a 1000 request per day limit and importing one new activity can
# take up to 3 API calls, 250 should be a safe number.
NUMBER_OF_NEW_ACTIVITIES_TO_PROCESS_PER_IMPORT=250
# The schedule to periodically run the import and HTML builds. Leave empty to disable periodic imports.
# The default schedule runs once a day at 04:05. If you do not know what cron expressions are, please leave this unchanged
# Make sure you don't run the imports too much to avoid hitting the Strava API rate limit. Once a day should be enough.
IMPORT_AND_BUILD_SCHEDULE="5 4 * * *"
# Set the timezone used for the schedule
# Valid timezones can found under TZ Identifier column here: https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List
TZ=Etc/GMT
# Allowed options: en_US, fr_FR, nl_BE or zh_CN
LOCALE=en_US
# Allowed options: metric or imperial
UNIT_SYSTEM=metric
# Time format to use when rendering the app
# Allowed formats: 24 or 12 (includes AM and PM)
TIME_FORMAT=24
# Date format to use when rendering the app
# Allowed formats: DAY-MONTH-YEAR or MONTH-DAY-YEAR
DATE_FORMAT=DAY-MONTH-YEAR
# Sport types to import. Leave empty to import all sport types
# With this list you can also decide the order the sport types will be rendered in.
# A full list of allowed options is available on https://github.com/robiningelbrecht/strava-statistics/wiki/Supported-sport-types/
SPORT_TYPES_TO_IMPORT='[]'
# Activity visibilities to import. Leave empty to import all visibilities
# This list can be combined with SPORT_TYPES_TO_IMPORT.
# Allowed values: ACTIVITY_VISIBILITIES_TO_IMPORT='["everyone", "followers_only", "only_me"]', 
ACTIVITY_VISIBILITIES_TO_IMPORT='[]'
# Optional, an array of activity ids to skip during import. 
# This allows you to skip specific activities during import.
# ACTIVITIES_TO_SKIP_DURING_IMPORT='["123456789", "987654321"]'
ACTIVITIES_TO_SKIP_DURING_IMPORT='[]'
# Your birthday. Needed to calculate heart rate zones.
ATHLETE_BIRTHDAY=YYYY-MM-DD
# History of weight (in kg or pounds, depending on UNIT_SYSTEM). Needed to calculate relative w/kg.
# Can also be filepath to .json file. Remember to add that file to docker compose
# Check https://github.com/robiningelbrecht/strava-statistics/wiki for more info.
#ATHLETE_WEIGHTS='/data/weight.json'
ATHLETE_WEIGHTS='{
    "YYYY-MM-DD": 74.6,
    "YYYY-MM-DD": 70.3
}'
# Calculate estimated FTP (eFTP) based on the activities in the last X months
# The eFTP will be used to calculate your activity intensity
# To disable eFTP leave this empty
CALCULATE_EFTP_BASED_ON_LAST_NUMBER_OF_MONTHS=4
# Optional, a link to your profile picture. Will be used to display in the nav bar and link to your Strava profile.
# Leave empty to disable this feature.
PROFILE_PICTURE_URL=''
# Optional, your Zwift level (1 - 100). Will be used to render your Zwift badge. Leave empty to disable this feature
ZWIFT_LEVEL=
# Optional, your Zwift racing score (0 - 1000). Will be used to add to your Zwift badge if ZWIFT_LEVEL is filled out.
ZWIFT_RACING_SCORE=
# Full URL with ntfy topic included. This topic will be used to notify you when a new HTML build has run.
# Leave empty to disable notifications.
NTFY_URL=''
# The UID and GID to create/own files managed by strava-statistics
# May only be necessary on Linux hosts, see File Permissions in Wiki
#PUID=
#PGID=
```

### weight.json

The weight can be imported from a JSON file. For example, the [WithingsToGarminSync](https://github.com/artop123/withings-to-garmin-sync) outputs ```withings.json```, which can be modified to the correct format using ```jq```

```bash
jq 'map(select(.Weight > 0) | {(.Date[0:10]): (.Weight * 100 | round / 100)}) | add' /path/to/withings.json > ./storage/weight.json
```

You can use ```crontab -e``` to schedule this.
