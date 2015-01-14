# Innmind

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/8522b504-4b17-4cd5-be98-68e068931132/big.png)](https://insight.sensiolabs.com/projects/65d26b50-67c4-47e7-a4fd-73e3fd3858a0)

## Dev environment

To load the full stack required to run the front app an be done in roughly 3 steps:

* Install [docker](https://www.docker.com/) (or via [`boot2docker`](http://boot2docker.io/) in you're not on linux)
* Run `docker build -t innmind .` in the project folder
* Run `./boot.sh` in the project folder

Once everything is loaded you can reach the app at `http://localhost:8080` from your browser.

Once you're done developping, you can take done the app simply by running `./shutdown.sh`.

**Important**: the docker stack is only intended for dev purposes here.
