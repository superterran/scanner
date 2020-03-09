FROM selenium/standalone-chrome-debug:3.6.0-bromine
RUN sudo apt-get update && sudo apt-get install -y composer